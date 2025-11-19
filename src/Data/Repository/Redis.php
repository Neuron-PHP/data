<?php

namespace Neuron\Data\Repository;

use RedisException;

/**
 * Redis-backed repository implementation for persistent data storage.
 *
 * The Redis repository provides persistent, distributed storage with automatic
 * serialization and TTL support.
 *
 * Requires the Redis PHP extension to be installed and a Redis server running.
 *
 * @package Neuron\Data\Repository
 *
 * @example
 * ```php
 * // Connect to Redis
 * $repo = new Redis([
 *     'host' => '127.0.0.1',
 *     'port' => 6379,
 *     'prefix' => 'myapp:'
 * ]);
 *
 * // Store a DTO
 * $repo->save('user:123', $userDto, 3600);
 *
 * // Retrieve it
 * $dto = $repo->find('user:123');
 * ```
 */
class Redis implements IRepository
{
	private ?\Redis $redis = null;
	private string $prefix;
	private array $config;

	/**
	 * Redis repository constructor.
	 *
	 * @param array $config Redis configuration with keys:
	 *                      - host: Redis server hostname (default: 127.0.0.1)
	 *                      - port: Redis server port (default: 6379)
	 *                      - database: Redis database index (default: 0)
	 *                      - prefix: Key prefix for all entries (default: '')
	 *                      - timeout: Connection timeout in seconds (default: 2.0)
	 *                      - auth: Authentication password (optional)
	 *                      - persistent: Use persistent connections (default: false)
	 * @throws \RuntimeException If Redis extension is not loaded
	 * @throws \RuntimeException If connection fails
	 */
	public function __construct( array $config = [] )
	{
		if( !extension_loaded( 'redis' ) )
		{
			throw new \RuntimeException( 'Redis extension is not loaded' );
		}

		$this->config = array_merge( [
			'host' => '127.0.0.1',
			'port' => 6379,
			'database' => 0,
			'prefix' => '',
			'timeout' => 2.0,
			'auth' => null,
			'persistent' => false
		], $config );

		$this->prefix = $this->config['prefix'];
		$this->connect();
	}

	/**
	 * Connect to Redis server.
	 *
	 * @return void
	 * @throws \RuntimeException If connection fails
	 */
	private function connect(): void
	{
		try
		{
			$this->redis = new \Redis();

			// Use persistent or regular connection
			if( $this->config['persistent'] )
			{
				$connected = $this->redis->pconnect(
					$this->config['host'],
					$this->config['port'],
					$this->config['timeout'],
					$this->config['host'] . ':' . $this->config['port']
				);
			}
			else
			{
				$connected = $this->redis->connect(
					$this->config['host'],
					$this->config['port'],
					$this->config['timeout']
				);
			}

			if( !$connected )
			{
				throw new \RuntimeException(
					sprintf(
						'Failed to connect to Redis server at %s:%d',
						$this->config['host'],
						$this->config['port']
					)
				);
			}

			// Authenticate if password is provided
			if( $this->config['auth'] !== null )
			{
				if( !$this->redis->auth( $this->config['auth'] ) )
				{
					throw new \RuntimeException( 'Redis authentication failed' );
				}
			}

			// Select database
			if( $this->config['database'] !== 0 )
			{
				$this->redis->select( $this->config['database'] );
			}

			// Enable automatic PHP serialization
			$this->redis->setOption( \Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP );
		}
		catch( RedisException $e )
		{
			throw new \RuntimeException(
				sprintf(
					'Redis connection error: %s',
					$e->getMessage()
				),
				0,
				$e
			);
		}
	}

	/**
	 * Save a value to Redis.
	 *
	 * @param string $key The unique key to store the value under
	 * @param mixed $value The value to store (will be serialized)
	 * @param int $ttl Time-to-live in seconds (0 = no expiration)
	 * @return bool True on success, false on failure
	 */
	public function save( string $key, mixed $value, int $ttl = 0 ): bool
	{
		if( !$this->redis )
		{
			return false;
		}

		try
		{
			$prefixedKey = $this->prefix . $key;

			if( $ttl > 0 )
			{
				return $this->redis->setex( $prefixedKey, $ttl, $value );
			}
			else
			{
				return $this->redis->set( $prefixedKey, $value );
			}
		}
		catch( RedisException $e )
		{
			return false;
		}
	}

	/**
	 * Find a value by key.
	 *
	 * @param string $key The key to look up
	 * @return mixed The stored value, or null if not found or expired
	 */
	public function find( string $key ): mixed
	{
		if( !$this->redis )
		{
			return null;
		}

		try
		{
			$prefixedKey = $this->prefix . $key;
			$value = $this->redis->get( $prefixedKey );

			return $value !== false ? $value : null;
		}
		catch( RedisException $e )
		{
			return null;
		}
	}

	/**
	 * Delete a value by key.
	 *
	 * @param string $key The key to delete
	 * @return bool True if deleted, false if key didn't exist
	 */
	public function delete( string $key ): bool
	{
		if( !$this->redis )
		{
			return false;
		}

		try
		{
			$prefixedKey = $this->prefix . $key;
			return $this->redis->del( $prefixedKey ) > 0;
		}
		catch( RedisException $e )
		{
			return false;
		}
	}

	/**
	 * Check if a key exists in Redis.
	 *
	 * @param string $key The key to check
	 * @return bool True if exists and not expired, false otherwise
	 */
	public function exists( string $key ): bool
	{
		if( !$this->redis )
		{
			return false;
		}

		try
		{
			$prefixedKey = $this->prefix . $key;
			return $this->redis->exists( $prefixedKey ) > 0;
		}
		catch( RedisException $e )
		{
			return false;
		}
	}

	/**
	 * Check if Redis connection is active.
	 *
	 * @return bool
	 */
	public function isConnected(): bool
	{
		if( !$this->redis )
		{
			return false;
		}

		try
		{
			return $this->redis->ping() !== false;
		}
		catch( RedisException $e )
		{
			return false;
		}
	}

	/**
	 * Get the underlying Redis instance.
	 *
	 * @return \Redis|null
	 */
	public function getRedis(): ?\Redis
	{
		return $this->redis;
	}

	/**
	 * Disconnect from Redis.
	 *
	 * @return void
	 */
	public function disconnect(): void
	{
		if( $this->redis )
		{
			try
			{
				$this->redis->close();
			}
			catch( RedisException $e )
			{
				// Ignore close errors
			}
			$this->redis = null;
		}
	}

	/**
	 * Destructor - ensure connection is closed.
	 */
	public function __destruct()
	{
		$this->disconnect();
	}
}
