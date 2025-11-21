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
 * // Connect using URL (recommended)
 * $repo = new Redis([
 *     'url' => 'redis://username:password@localhost:6379/0',
 *     'prefix' => 'myapp:'
 * ]);
 *
 * // Connect using traditional config
 * $repo = new Redis([
 *     'host' => '127.0.0.1',
 *     'port' => 6379,
 *     'auth' => ['username', 'password'], // ACL auth (Redis 6.0+)
 *     'database' => 0,
 *     'prefix' => 'myapp:'
 * ]);
 *
 * // Connect with password-only auth (Redis < 6.0)
 * $repo = new Redis([
 *     'host' => '127.0.0.1',
 *     'auth' => 'mypassword',
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
	 *                      - url: Redis URL (e.g., redis://user:pass@host:port/db) (optional)
	 *                      - host: Redis server hostname (default: 127.0.0.1)
	 *                      - port: Redis server port (default: 6379)
	 *                      - database: Redis database index (default: 0)
	 *                      - prefix: Key prefix for all entries (default: '')
	 *                      - timeout: Connection timeout in seconds (default: 2.0)
	 *                      - auth: Authentication - string for password-only (Redis < 6.0)
	 *                              or array ['username', 'password'] for ACL (Redis 6.0+) (optional)
	 *                      - persistent: Use persistent connections (default: false)
	 *                      - ssl: Use SSL/TLS connection (default: false, auto-detected from rediss:// URL)
	 * @throws \RuntimeException If Redis extension is not loaded
	 * @throws \RuntimeException If connection fails
	 * @throws \InvalidArgumentException If URL format is invalid
	 */
	public function __construct( array $config = [] )
	{
		if( !extension_loaded( 'redis' ) )
		{
			throw new \RuntimeException( 'Redis extension is not loaded' );
		}

		// Parse URL if provided
		$parsedConfig = [];
		if( isset( $config['url'] ) )
		{
			$parsedConfig = $this->parseRedisUrl( $config['url'] );
			unset( $config['url'] );
		}

		// Merge: defaults < parsed URL < explicit config
		$this->config = array_merge( [
			'host' => '127.0.0.1',
			'port' => 6379,
			'database' => 0,
			'prefix' => '',
			'timeout' => 2.0,
			'auth' => null,
			'persistent' => false,
			'ssl' => false
		], $parsedConfig, $config );

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

			// Authenticate if credentials are provided
			if( $this->config['auth'] !== null )
			{
				$authResult = false;

				// Support both ACL (username + password) and legacy (password only) authentication
				if( is_array( $this->config['auth'] ) )
				{
					// Redis 6.0+ ACL authentication with username and password
					$authResult = $this->redis->auth( $this->config['auth'] );
				}
				else
				{
					// Legacy password-only authentication
					$authResult = $this->redis->auth( $this->config['auth'] );
				}

				if( !$authResult )
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
	 * Parse a Redis URL into connection configuration.
	 *
	 * Supports both redis:// and rediss:// (SSL/TLS) schemes.
	 *
	 * @param string $url Redis URL in format: redis://[username:password@]host[:port][/database]
	 * @return array Parsed configuration array
	 * @throws \InvalidArgumentException If URL format is invalid
	 */
	private function parseRedisUrl( string $url ): array
	{
		$parsed = parse_url( $url );

		if( $parsed === false )
		{
			throw new \InvalidArgumentException( 'Invalid Redis URL format' );
		}

		// Validate scheme
		if( !isset( $parsed['scheme'] ) || !in_array( $parsed['scheme'], [ 'redis', 'rediss' ] ) )
		{
			throw new \InvalidArgumentException( 'Redis URL must use redis:// or rediss:// scheme' );
		}

		// Extract configuration
		$config = [
			'host' => $parsed['host'] ?? '127.0.0.1',
			'port' => $parsed['port'] ?? 6379,
			'database' => 0,
			'ssl' => $parsed['scheme'] === 'rediss'
		];

		// Extract database from path (e.g., /1 means database 1)
		if( isset( $parsed['path'] ) && $parsed['path'] !== '/' )
		{
			$database = ltrim( $parsed['path'], '/' );
			if( is_numeric( $database ) )
			{
				$config['database'] = (int)$database;
			}
		}

		// Extract authentication
		if( isset( $parsed['user'] ) || isset( $parsed['pass'] ) )
		{
			$username = $parsed['user'] ?? null;
			$password = $parsed['pass'] ?? null;

			// If both username and password are provided, use ACL format
			if( $username !== null && $password !== null )
			{
				$config['auth'] = [ $username, $password ];
			}
			// If only password is provided (username is empty), use password-only format
			elseif( $password !== null )
			{
				$config['auth'] = $password;
			}
		}

		return $config;
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
