<?php

namespace Neuron\Data\Database;

use InvalidArgumentException;
use PDO;

/**
 * Builds PDO connections from Neuron database configuration.
 *
 * This is the single place the framework turns a `database:` settings section
 * into a connection, so every process that touches the database (web request,
 * scheduler, queue worker, CLI command) gets the same DSN rules and the same
 * per-adapter initialisation (SQLite WAL + busy timeout, UTC on MySQL and
 * PostgreSQL).
 *
 * Configuration may be given as individual keys, a URL, or both. When both are
 * present the individual keys win:
 *
 * ```yaml
 * database:
 *   adapter: mysql
 *   host: localhost
 *   port: 3306
 *   name: app
 *   user: app
 *   pass: secret
 *   charset: utf8mb4
 *
 * database:
 *   url: mysql://app:secret@localhost:3306/app?charset=utf8mb4
 *
 * database:
 *   url: sqlite:///var/www/app/storage/database.sqlite3
 * ```
 *
 * A `DATABASE_URL` environment variable, as injected by Railway, Heroku and
 * docker-compose, overrides whatever the configuration file says for the
 * components it carries (adapter, host, port, name, user, pass and a `charset`
 * query parameter). File keys the URL does not mention are kept, so a file
 * `charset:` still applies to a URL without one. This lets one image run
 * against different databases with no config edit.
 *
 * @package Neuron\Data\Database
 */
class ConnectionFactory
{
	/** Environment variable consulted by normalize(). */
	public const ENV_URL = 'DATABASE_URL';

	/**
	 * Create a PDO connection from a database configuration array.
	 *
	 * @param array $config Database configuration (see class docblock)
	 * @param array $options Additional PDO driver options merged over the defaults
	 * @return PDO
	 * @throws InvalidArgumentException if the configuration is invalid
	 */
	public static function createFromConfig( array $config, array $options = [] ): PDO
	{
		$config = self::normalize( $config );

		$pdo = new PDO(
			self::buildDsn( $config ),
			$config['user'] ?? null,
			$config['pass'] ?? null,
			$options + [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]
		);

		self::initializeConnection( $pdo, $config['adapter'] );

		return $pdo;
	}

	/**
	 * Resolve a configuration array into its canonical form.
	 *
	 * Parses `url` when present, merges it beneath any explicit keys, maps the
	 * `username` / `password` aliases onto `user` / `pass`, and defaults the
	 * adapter to sqlite. When `DATABASE_URL` is set in the environment (and
	 * `$useEnvironment` is true) it is applied last, over the file's values.
	 *
	 * @param array $config
	 * @param bool $useEnvironment Honour DATABASE_URL; pass false to resolve the file alone
	 * @return array Canonical config with at least `adapter` and `name`
	 * @throws InvalidArgumentException if the adapter or name is missing/unsupported
	 */
	public static function normalize( array $config, bool $useEnvironment = true ): array
	{
		if( !empty( $config['url'] ) )
		{
			$urlConfig = self::parseUrl( (string)$config['url'] );

			// Explicit keys take precedence over URL-derived values.
			$explicit = array_filter(
				$config,
				fn( $value, $key ) => $key !== 'url' && $value !== null,
				ARRAY_FILTER_USE_BOTH
			);

			$config = array_merge( $urlConfig, $explicit );
		}

		unset( $config['url'] );

		$envUrl = $useEnvironment ? self::environmentUrl() : null;

		if( $envUrl !== null )
		{
			// The environment describes the database this process should use;
			// it beats the file, which describes a default.
			$config = array_merge( $config, self::parseUrl( $envUrl ) );
		}

		if( !isset( $config['user'] ) && isset( $config['username'] ) )
		{
			$config['user'] = $config['username'];
		}

		if( !isset( $config['pass'] ) && isset( $config['password'] ) )
		{
			$config['pass'] = $config['password'];
		}

		unset( $config['username'], $config['password'] );

		$config['adapter'] = $config['adapter'] ?? 'sqlite';

		if( !in_array( $config['adapter'], [ 'sqlite', 'mysql', 'pgsql' ], true ) )
		{
			throw new InvalidArgumentException( "Unsupported database adapter: {$config['adapter']}" );
		}

		if( empty( $config['name'] ) )
		{
			throw new InvalidArgumentException(
				sprintf( 'Database "name" configuration is required for %s connections.', $config['adapter'] )
			);
		}

		return $config;
	}

	/**
	 * The DATABASE_URL environment variable, or null when unset or blank.
	 *
	 * @return string|null
	 */
	public static function environmentUrl(): ?string
	{
		$url = getenv( self::ENV_URL );

		if( $url === false || trim( $url ) === '' )
		{
			return null;
		}

		return trim( $url );
	}

	/**
	 * Build a PDO DSN from a canonical configuration array.
	 *
	 * @param array $config Output of normalize()
	 * @return string
	 * @throws InvalidArgumentException if the adapter is unsupported
	 */
	public static function buildDsn( array $config ): string
	{
		$adapter = $config['adapter'] ?? 'sqlite';

		return match( $adapter )
		{
			'sqlite' => "sqlite:{$config['name']}",
			'mysql' => sprintf(
				'mysql:host=%s;port=%s;dbname=%s;charset=%s',
				$config['host'] ?? 'localhost',
				$config['port'] ?? 3306,
				$config['name'],
				$config['charset'] ?? 'utf8mb4'
			),
			'pgsql' => sprintf(
				'pgsql:host=%s;port=%s;dbname=%s',
				$config['host'] ?? 'localhost',
				$config['port'] ?? 5432,
				$config['name']
			),
			default => throw new InvalidArgumentException( "Unsupported database adapter: {$adapter}" )
		};
	}

	/**
	 * Parse a database URL into a configuration array.
	 *
	 * Supported forms:
	 * - postgresql://user:pass@host:5432/dbname?charset=utf8
	 * - mysql://user:pass@host:3306/dbname?charset=utf8mb4
	 * - sqlite:///absolute/path/to/database.db
	 * - sqlite://relative/path/to/database.db
	 * - sqlite::memory:
	 *
	 * User and password components are URL-decoded, so `%40` becomes `@`.
	 *
	 * @param string $url
	 * @return array
	 * @throws InvalidArgumentException if the URL is malformed or the scheme unsupported
	 */
	public static function parseUrl( string $url ): array
	{
		if( str_starts_with( $url, 'sqlite:' ) )
		{
			$path = substr( $url, 7 );

			if( $path === ':memory:' )
			{
				return [ 'adapter' => 'sqlite', 'name' => ':memory:' ];
			}

			// sqlite:///abs/path -> /abs/path ; sqlite://rel/path -> rel/path
			if( str_starts_with( $path, '///' ) )
			{
				$path = substr( $path, 2 );
			}
			elseif( str_starts_with( $path, '//' ) )
			{
				$path = substr( $path, 2 );
			}

			return [ 'adapter' => 'sqlite', 'name' => $path ];
		}

		$parsed = parse_url( $url );

		if( $parsed === false || !isset( $parsed['scheme'] ) )
		{
			throw new InvalidArgumentException( "Malformed database URL: {$url}" );
		}

		$adapter = match( $parsed['scheme'] )
		{
			'mysql' => 'mysql',
			'postgresql', 'postgres', 'pgsql' => 'pgsql',
			default => throw new InvalidArgumentException( "Unsupported database scheme: {$parsed['scheme']}" )
		};

		$config = [ 'adapter' => $adapter ];

		if( isset( $parsed['host'] ) )
		{
			$config['host'] = $parsed['host'];
		}

		if( isset( $parsed['port'] ) )
		{
			$config['port'] = $parsed['port'];
		}

		if( isset( $parsed['user'] ) )
		{
			$config['user'] = rawurldecode( $parsed['user'] );
		}

		if( isset( $parsed['pass'] ) )
		{
			$config['pass'] = rawurldecode( $parsed['pass'] );
		}

		if( isset( $parsed['path'] ) && $parsed['path'] !== '/' )
		{
			$config['name'] = ltrim( $parsed['path'], '/' );
		}

		if( isset( $parsed['query'] ) )
		{
			parse_str( $parsed['query'], $queryParams );

			if( isset( $queryParams['charset'] ) )
			{
				$config['charset'] = $queryParams['charset'];
			}
		}

		return $config;
	}

	/**
	 * Apply adapter-specific session settings to a fresh connection.
	 *
	 * @param PDO $pdo
	 * @param string $adapter
	 * @return void
	 */
	public static function initializeConnection( PDO $pdo, string $adapter ): void
	{
		match( $adapter )
		{
			'sqlite' => self::initializeSqlite( $pdo ),
			'mysql' => self::initializeMysql( $pdo ),
			'pgsql' => self::initializePostgresql( $pdo ),
			default => null
		};
	}

	/**
	 * Foreign keys on, WAL journal for concurrent readers/writers, and a busy
	 * timeout so several processes (web, scheduler, worker) sharing one file
	 * wait rather than fail with "database is locked".
	 *
	 * @param PDO $pdo
	 * @return void
	 */
	private static function initializeSqlite( PDO $pdo ): void
	{
		$pdo->exec( 'PRAGMA foreign_keys = ON' );
		$pdo->exec( 'PRAGMA journal_mode = WAL' );
		$pdo->exec( 'PRAGMA busy_timeout = 5000' );
	}

	/**
	 * @param PDO $pdo
	 * @return void
	 */
	private static function initializeMysql( PDO $pdo ): void
	{
		$pdo->exec( "SET time_zone = '+00:00'" );
	}

	/**
	 * @param PDO $pdo
	 * @return void
	 */
	private static function initializePostgresql( PDO $pdo ): void
	{
		$pdo->exec( "SET NAMES 'UTF8'" );
		$pdo->exec( "SET timezone = 'UTC'" );
	}
}
