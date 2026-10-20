<?php

namespace Data\Database;

use InvalidArgumentException;
use Neuron\Data\Database\ConnectionFactory;
use PDO;
use PHPUnit\Framework\TestCase;

class ConnectionFactoryTest extends TestCase
{
	private string|false $previousEnvUrl = false;

	protected function setUp(): void
	{
		parent::setUp();
		$this->previousEnvUrl = getenv( ConnectionFactory::ENV_URL );
		putenv( ConnectionFactory::ENV_URL );
	}

	protected function tearDown(): void
	{
		if( $this->previousEnvUrl === false )
		{
			putenv( ConnectionFactory::ENV_URL );
		}
		else
		{
			putenv( ConnectionFactory::ENV_URL . '=' . $this->previousEnvUrl );
		}

		parent::tearDown();
	}

	// DATABASE_URL

	public function testEnvironmentUrlIsNullWhenUnsetOrBlank(): void
	{
		$this->assertNull( ConnectionFactory::environmentUrl() );

		putenv( 'DATABASE_URL=   ' );
		$this->assertNull( ConnectionFactory::environmentUrl() );
	}

	public function testDatabaseUrlEnvironmentOverridesFileValues(): void
	{
		putenv( 'DATABASE_URL=postgresql://railway:pw@db.internal:5432/prod' );

		$config = ConnectionFactory::normalize( [
			'adapter' => 'sqlite',
			'name' => 'storage/database.sqlite3',
			'charset' => 'utf8mb4',
		] );

		$this->assertSame( 'pgsql', $config['adapter'] );
		$this->assertSame( 'db.internal', $config['host'] );
		$this->assertSame( 5432, $config['port'] );
		$this->assertSame( 'prod', $config['name'] );
		$this->assertSame( 'railway', $config['user'] );
		$this->assertSame( 'pw', $config['pass'] );
		$this->assertSame( 'utf8mb4', $config['charset'], 'file keys the URL does not carry are kept' );
	}

	public function testDatabaseUrlEnvironmentBeatsFileUrl(): void
	{
		putenv( 'DATABASE_URL=mysql://env:e@envhost/envdb' );

		$config = ConnectionFactory::normalize( [ 'url' => 'mysql://file:f@filehost/filedb' ] );

		$this->assertSame( 'envhost', $config['host'] );
		$this->assertSame( 'envdb', $config['name'] );
		$this->assertSame( 'env', $config['user'] );
	}

	public function testDatabaseUrlEnvironmentSuppliesWholeConfig(): void
	{
		putenv( 'DATABASE_URL=sqlite:///tmp/env.sqlite3' );

		$config = ConnectionFactory::normalize( [] );

		$this->assertSame( [ 'adapter' => 'sqlite', 'name' => '/tmp/env.sqlite3' ], $config );
	}

	public function testDatabaseUrlEnvironmentCanBeIgnored(): void
	{
		putenv( 'DATABASE_URL=mysql://env:e@envhost/envdb' );

		$config = ConnectionFactory::normalize( [ 'adapter' => 'sqlite', 'name' => 'file.db' ], false );

		$this->assertSame( 'sqlite', $config['adapter'] );
		$this->assertSame( 'file.db', $config['name'] );
	}

	public function testMalformedDatabaseUrlEnvironmentThrows(): void
	{
		putenv( 'DATABASE_URL=oracle://x@y/z' );

		$this->expectException( InvalidArgumentException::class );

		ConnectionFactory::normalize( [ 'adapter' => 'sqlite', 'name' => 'file.db' ] );
	}

	// parseUrl

	public function testParseMysqlUrl(): void
	{
		$config = ConnectionFactory::parseUrl( 'mysql://app:secret@db.example.com:3307/appdb?charset=utf8' );

		$this->assertSame( 'mysql', $config['adapter'] );
		$this->assertSame( 'db.example.com', $config['host'] );
		$this->assertSame( 3307, $config['port'] );
		$this->assertSame( 'app', $config['user'] );
		$this->assertSame( 'secret', $config['pass'] );
		$this->assertSame( 'appdb', $config['name'] );
		$this->assertSame( 'utf8', $config['charset'] );
	}

	public function testParsePostgresUrlSchemes(): void
	{
		foreach( [ 'postgresql', 'postgres', 'pgsql' ] as $scheme )
		{
			$config = ConnectionFactory::parseUrl( "{$scheme}://u:p@localhost/db" );
			$this->assertSame( 'pgsql', $config['adapter'], $scheme );
			$this->assertSame( 'db', $config['name'] );
		}
	}

	public function testParseUrlDecodesCredentials(): void
	{
		$config = ConnectionFactory::parseUrl( 'mysql://user%40site:p%40ss%3Aword@localhost/db' );

		$this->assertSame( 'user@site', $config['user'] );
		$this->assertSame( 'p@ss:word', $config['pass'] );
	}

	public function testParseSqliteMemoryUrl(): void
	{
		$this->assertSame(
			[ 'adapter' => 'sqlite', 'name' => ':memory:' ],
			ConnectionFactory::parseUrl( 'sqlite::memory:' )
		);
	}

	public function testParseSqliteAbsoluteAndRelativeUrls(): void
	{
		$this->assertSame( '/var/app/db.sqlite', ConnectionFactory::parseUrl( 'sqlite:///var/app/db.sqlite' )['name'] );
		$this->assertSame( 'storage/db.sqlite', ConnectionFactory::parseUrl( 'sqlite://storage/db.sqlite' )['name'] );
	}

	public function testParseUrlWithoutDatabaseNameOmitsName(): void
	{
		$config = ConnectionFactory::parseUrl( 'mysql://localhost/' );

		$this->assertArrayNotHasKey( 'name', $config );
	}

	public function testParseMalformedUrlThrows(): void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Malformed database URL' );

		ConnectionFactory::parseUrl( 'not a url' );
	}

	public function testParseUnsupportedSchemeThrows(): void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unsupported database scheme: mongodb' );

		ConnectionFactory::parseUrl( 'mongodb://localhost/db' );
	}

	// normalize

	public function testNormalizeDefaultsAdapterToSqlite(): void
	{
		$config = ConnectionFactory::normalize( [ 'name' => ':memory:' ] );

		$this->assertSame( 'sqlite', $config['adapter'] );
	}

	public function testNormalizeExplicitKeysOverrideUrl(): void
	{
		$config = ConnectionFactory::normalize( [
			'url' => 'mysql://urluser:urlpass@urlhost:3306/urldb',
			'host' => 'explicit-host',
			'pass' => null,
		] );

		$this->assertSame( 'explicit-host', $config['host'] );
		$this->assertSame( 'urluser', $config['user'] );
		$this->assertSame( 'urlpass', $config['pass'], 'null explicit values must not clobber URL values' );
		$this->assertSame( 'urldb', $config['name'] );
		$this->assertArrayNotHasKey( 'url', $config );
	}

	public function testNormalizeMapsUsernamePasswordAliases(): void
	{
		$config = ConnectionFactory::normalize( [
			'adapter' => 'mysql',
			'name' => 'db',
			'username' => 'alias-user',
			'password' => 'alias-pass',
		] );

		$this->assertSame( 'alias-user', $config['user'] );
		$this->assertSame( 'alias-pass', $config['pass'] );
		$this->assertArrayNotHasKey( 'username', $config );
		$this->assertArrayNotHasKey( 'password', $config );
	}

	public function testNormalizeRequiresName(): void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Database "name" configuration is required for mysql connections.' );

		ConnectionFactory::normalize( [ 'adapter' => 'mysql' ] );
	}

	public function testNormalizeRejectsUnsupportedAdapter(): void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unsupported database adapter: oracle' );

		ConnectionFactory::normalize( [ 'adapter' => 'oracle', 'name' => 'db' ] );
	}

	// buildDsn

	public function testBuildDsnForEachAdapter(): void
	{
		$this->assertSame( 'sqlite:/tmp/x.db', ConnectionFactory::buildDsn( [ 'adapter' => 'sqlite', 'name' => '/tmp/x.db' ] ) );

		$this->assertSame(
			'mysql:host=localhost;port=3306;dbname=db;charset=utf8mb4',
			ConnectionFactory::buildDsn( [ 'adapter' => 'mysql', 'name' => 'db' ] )
		);

		$this->assertSame(
			'pgsql:host=pg;port=5433;dbname=db',
			ConnectionFactory::buildDsn( [ 'adapter' => 'pgsql', 'name' => 'db', 'host' => 'pg', 'port' => 5433 ] )
		);
	}

	// createFromConfig

	public function testCreateSqliteConnectionAppliesPragmas(): void
	{
		$pdo = ConnectionFactory::createFromConfig( [ 'url' => 'sqlite::memory:' ] );

		$this->assertInstanceOf( PDO::class, $pdo );
		$this->assertSame( PDO::ERRMODE_EXCEPTION, $pdo->getAttribute( PDO::ATTR_ERRMODE ) );
		$this->assertSame( 1, (int)$pdo->query( 'PRAGMA foreign_keys' )->fetchColumn() );
		$this->assertSame( 5000, (int)$pdo->query( 'PRAGMA busy_timeout' )->fetchColumn() );
	}

	public function testCreateFromConfigMergesCallerOptions(): void
	{
		$pdo = ConnectionFactory::createFromConfig(
			[ 'adapter' => 'sqlite', 'name' => ':memory:' ],
			[ PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM ]
		);

		$this->assertSame( PDO::FETCH_NUM, $pdo->getAttribute( PDO::ATTR_DEFAULT_FETCH_MODE ) );
	}
}
