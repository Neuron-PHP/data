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

		// Delete all test keys
		$iterator = null;
		$keys = $redis->scan( $iterator, 'test:repo:*', 100 );

		if( $keys !== false && !empty( $keys ) )
		{
			$redis->del( ...$keys );
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
		$this->assertTrue( $redis->exists( 'test:repo:mykey' ) );
		$this->assertFalse( $redis->exists( 'mykey' ) );
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
}
