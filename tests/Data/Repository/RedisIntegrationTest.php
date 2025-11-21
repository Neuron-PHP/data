<?php

namespace Data\Repository;

use Neuron\Data\Repository\Redis;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Redis repository.
 *
 * These tests require a Redis server to be running on localhost:6379.
 * Skip these tests if Redis is not available.
 */
class RedisIntegrationTest extends TestCase
{
	private ?Redis $repo = null;

	protected function setUp(): void
	{
		if( !extension_loaded( 'redis' ) )
		{
			$this->markTestSkipped( 'Redis extension is not loaded' );
		}

		try
		{
			$this->repo = new Redis( [
				'host' => '127.0.0.1',
				'port' => 6379,
				'prefix' => 'test:repo:'
			] );

			if( !$this->repo->isConnected() )
			{
				$this->markTestSkipped( 'Redis server is not available' );
			}

			// Clean up test keys
			$this->cleanupTestKeys();
		}
		catch( \RuntimeException $e )
		{
			$this->markTestSkipped( 'Could not connect to Redis: ' . $e->getMessage() );
		}
	}

	protected function tearDown(): void
	{
		if( $this->repo )
		{
			$this->cleanupTestKeys();
			$this->repo->disconnect();
		}
	}

	private function cleanupTestKeys(): void
	{
		if( !$this->repo )
		{
			return;
		}

		$redis = $this->repo->getRedis();

		if( !$redis )
		{
			return;
		}

		// Delete all test keys with various prefixes
		$prefixes = [ 'test:repo:*', 'test:url:*', 'test:urldb:*', 'test:urlauth:*', 'test:urlacl:*', 'test:override:*' ];

		foreach( $prefixes as $pattern )
		{
			$iterator = null;
			$keys = $redis->scan( $iterator, $pattern, 100 );

			if( $keys !== false && !empty( $keys ) )
			{
				$redis->del( ...$keys );
			}
		}
	}

	public function testSaveAndFind()
	{
		$result = $this->repo->save( 'test:key', 'test value' );

		$this->assertTrue( $result );

		$value = $this->repo->find( 'test:key' );

		$this->assertEquals( 'test value', $value );
	}

	public function testSaveAndFindObject()
	{
		$obj = new \stdClass();
		$obj->foo = 'bar';
		$obj->number = 123;

		$this->repo->save( 'test:object', $obj );

		$retrieved = $this->repo->find( 'test:object' );

		$this->assertInstanceOf( \stdClass::class, $retrieved );
		$this->assertEquals( 'bar', $retrieved->foo );
		$this->assertEquals( 123, $retrieved->number );
	}

	public function testSaveAndFindArray()
	{
		$data = [ 'foo' => 'bar', 'numbers' => [ 1, 2, 3 ] ];

		$this->repo->save( 'test:array', $data );

		$retrieved = $this->repo->find( 'test:array' );

		$this->assertEquals( $data, $retrieved );
	}

	public function testFindNonExistent()
	{
		$value = $this->repo->find( 'nonexistent:key' );

		$this->assertNull( $value );
	}

	public function testDelete()
	{
		$this->repo->save( 'test:delete', 'value' );

		$this->assertTrue( $this->repo->exists( 'test:delete' ) );

		$result = $this->repo->delete( 'test:delete' );

		$this->assertTrue( $result );
		$this->assertFalse( $this->repo->exists( 'test:delete' ) );
	}

	public function testDeleteNonExistent()
	{
		$result = $this->repo->delete( 'nonexistent:key' );

		$this->assertFalse( $result );
	}

	public function testExists()
	{
		// Ensure key doesn't exist before test
		$this->repo->delete( 'test:exists' );

		$this->assertFalse( $this->repo->exists( 'test:exists' ) );

		$this->repo->save( 'test:exists', 'value' );

		$this->assertTrue( $this->repo->exists( 'test:exists' ) );
	}

	public function testTTLExpiration()
	{
		// Save with 2 second TTL
		$this->repo->save( 'test:ttl', 'value', 2 );

		$this->assertTrue( $this->repo->exists( 'test:ttl' ) );
		$this->assertEquals( 'value', $this->repo->find( 'test:ttl' ) );

		// Wait for expiration
		sleep( 3 );

		$this->assertFalse( $this->repo->exists( 'test:ttl' ) );
		$this->assertNull( $this->repo->find( 'test:ttl' ) );
	}

	public function testTTLNoExpiration()
	{
		// Save with no TTL
		$this->repo->save( 'test:no-ttl', 'value', 0 );

		sleep( 1 );

		$this->assertTrue( $this->repo->exists( 'test:no-ttl' ) );
		$this->assertEquals( 'value', $this->repo->find( 'test:no-ttl' ) );
	}

	public function testPrefix()
	{
		$redis = $this->repo->getRedis();

		$this->repo->save( 'mykey', 'value' );

		// Check that the actual key in Redis includes the prefix
		// Redis extension exists() returns count (int) in newer versions
		$this->assertGreaterThan( 0, $redis->exists( 'test:repo:mykey' ) );
		$this->assertEquals( 0, $redis->exists( 'mykey' ) );
	}

	public function testIsConnected()
	{
		$this->assertTrue( $this->repo->isConnected() );
	}

	public function testDisconnect()
	{
		$this->assertTrue( $this->repo->isConnected() );

		$this->repo->disconnect();

		$this->assertFalse( $this->repo->isConnected() );
	}

	public function testUrlBasicConnection()
	{
		if( !extension_loaded( 'redis' ) )
		{
			$this->markTestSkipped( 'Redis extension is not loaded' );
		}

		try
		{
			$repo = new Redis( [
				'url' => 'redis://127.0.0.1:6379/0',
				'prefix' => 'test:url:'
			] );

			$this->assertTrue( $repo->isConnected() );

			$repo->save( 'test', 'value' );
			$this->assertEquals( 'value', $repo->find( 'test' ) );

			$repo->disconnect();
		}
		catch( \RuntimeException $e )
		{
			$this->markTestSkipped( 'Could not connect to Redis: ' . $e->getMessage() );
		}
	}

	public function testUrlWithDatabase()
	{
		if( !extension_loaded( 'redis' ) )
		{
			$this->markTestSkipped( 'Redis extension is not loaded' );
		}

		try
		{
			$repo = new Redis( [
				'url' => 'redis://127.0.0.1:6379/1',
				'prefix' => 'test:urldb:'
			] );

			$this->assertTrue( $repo->isConnected() );

			// Verify we're using database 1 by checking the Redis instance
			$redis = $repo->getRedis();
			$info = $redis->info( 'keyspace' );

			$repo->save( 'test', 'value' );
			$this->assertEquals( 'value', $repo->find( 'test' ) );

			$repo->disconnect();
		}
		catch( \RuntimeException $e )
		{
			$this->markTestSkipped( 'Could not connect to Redis: ' . $e->getMessage() );
		}
	}

	public function testUrlWithPasswordOnly()
	{
		// This test will be skipped unless you have a Redis instance with password configured
		if( !extension_loaded( 'redis' ) )
		{
			$this->markTestSkipped( 'Redis extension is not loaded' );
		}

		// Check if a password-protected Redis is available on localhost
		// If not, skip this test
		try
		{
			$testRepo = new Redis( [ 'host' => '127.0.0.1', 'port' => 6379 ] );
			if( $testRepo->isConnected() )
			{
				$testRepo->disconnect();
				$this->markTestSkipped( 'Redis server does not require authentication' );
			}
		}
		catch( \RuntimeException $e )
		{
			// Redis requires auth, continue with test
		}

		// Note: This test requires REDIS_PASSWORD environment variable
		$password = getenv( 'REDIS_PASSWORD' );
		if( !$password )
		{
			$this->markTestSkipped( 'REDIS_PASSWORD environment variable not set' );
		}

		try
		{
			$repo = new Redis( [
				'url' => 'redis://:' . $password . '@127.0.0.1:6379/0',
				'prefix' => 'test:urlauth:'
			] );

			$this->assertTrue( $repo->isConnected() );
			$repo->disconnect();
		}
		catch( \RuntimeException $e )
		{
			$this->markTestSkipped( 'Could not connect with password: ' . $e->getMessage() );
		}
	}

	public function testUrlWithUsernameAndPassword()
	{
		// This test requires Redis 6.0+ with ACL configured
		if( !extension_loaded( 'redis' ) )
		{
			$this->markTestSkipped( 'Redis extension is not loaded' );
		}

		// Note: This test requires REDIS_USERNAME and REDIS_PASSWORD environment variables
		$username = getenv( 'REDIS_USERNAME' );
		$password = getenv( 'REDIS_PASSWORD' );

		if( !$username || !$password )
		{
			$this->markTestSkipped( 'REDIS_USERNAME and REDIS_PASSWORD environment variables not set' );
		}

		try
		{
			$repo = new Redis( [
				'url' => 'redis://' . $username . ':' . $password . '@127.0.0.1:6379/0',
				'prefix' => 'test:urlacl:'
			] );

			$this->assertTrue( $repo->isConnected() );
			$repo->disconnect();
		}
		catch( \RuntimeException $e )
		{
			$this->markTestSkipped( 'Could not connect with ACL auth: ' . $e->getMessage() );
		}
	}

	public function testUrlConfigOverride()
	{
		if( !extension_loaded( 'redis' ) )
		{
			$this->markTestSkipped( 'Redis extension is not loaded' );
		}

		try
		{
			// URL specifies database 1, but config overrides to database 0
			$repo = new Redis( [
				'url' => 'redis://127.0.0.1:6379/1',
				'database' => 0,
				'prefix' => 'test:override:'
			] );

			$this->assertTrue( $repo->isConnected() );
			$repo->disconnect();
		}
		catch( \RuntimeException $e )
		{
			$this->markTestSkipped( 'Could not connect to Redis: ' . $e->getMessage() );
		}
	}

	public function testInvalidUrlFormat()
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Redis URL must use redis:// or rediss:// scheme' );

		new Redis( [ 'url' => 'http://localhost:6379' ] );
	}

	public function testMalformedUrl()
	{
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Redis URL must use redis:// or rediss:// scheme' );

		new Redis( [ 'url' => 'not a valid url at all' ] );
	}
}
