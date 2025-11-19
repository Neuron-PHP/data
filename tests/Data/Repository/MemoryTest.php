<?php

namespace Data\Repository;

use Neuron\Data\Repository\Memory;
use PHPUnit\Framework\TestCase;

class MemoryTest extends TestCase
{
	public function testSaveAndFind()
	{
		$repo = new Memory();

		$result = $repo->save( 'test:key', 'test value' );

		$this->assertTrue( $result );

		$value = $repo->find( 'test:key' );

		$this->assertEquals( 'test value', $value );
	}

	public function testSaveAndFindObject()
	{
		$repo = new Memory();

		$obj = new \stdClass();
		$obj->foo = 'bar';
		$obj->number = 123;

		$repo->save( 'test:object', $obj );

		$retrieved = $repo->find( 'test:object' );

		$this->assertInstanceOf( \stdClass::class, $retrieved );
		$this->assertEquals( 'bar', $retrieved->foo );
		$this->assertEquals( 123, $retrieved->number );
	}

	public function testSaveAndFindArray()
	{
		$repo = new Memory();

		$data = [ 'foo' => 'bar', 'numbers' => [ 1, 2, 3 ] ];

		$repo->save( 'test:array', $data );

		$retrieved = $repo->find( 'test:array' );

		$this->assertEquals( $data, $retrieved );
	}

	public function testFindNonExistent()
	{
		$repo = new Memory();

		$value = $repo->find( 'nonexistent:key' );

		$this->assertNull( $value );
	}

	public function testDelete()
	{
		$repo = new Memory();

		$repo->save( 'test:delete', 'value' );

		$this->assertTrue( $repo->exists( 'test:delete' ) );

		$result = $repo->delete( 'test:delete' );

		$this->assertTrue( $result );
		$this->assertFalse( $repo->exists( 'test:delete' ) );
	}

	public function testDeleteNonExistent()
	{
		$repo = new Memory();

		$result = $repo->delete( 'nonexistent:key' );

		$this->assertFalse( $result );
	}

	public function testExists()
	{
		$repo = new Memory();

		$this->assertFalse( $repo->exists( 'test:exists' ) );

		$repo->save( 'test:exists', 'value' );

		$this->assertTrue( $repo->exists( 'test:exists' ) );
	}

	public function testTTLExpiration()
	{
		$repo = new Memory();

		// Save with 1 second TTL
		$repo->save( 'test:ttl', 'value', 1 );

		$this->assertTrue( $repo->exists( 'test:ttl' ) );
		$this->assertEquals( 'value', $repo->find( 'test:ttl' ) );

		// Wait for expiration
		sleep( 2 );

		$this->assertFalse( $repo->exists( 'test:ttl' ) );
		$this->assertNull( $repo->find( 'test:ttl' ) );
	}

	public function testTTLNoExpiration()
	{
		$repo = new Memory();

		// Save with no TTL
		$repo->save( 'test:no-ttl', 'value', 0 );

		sleep( 1 );

		$this->assertTrue( $repo->exists( 'test:no-ttl' ) );
		$this->assertEquals( 'value', $repo->find( 'test:no-ttl' ) );
	}

	public function testClear()
	{
		$repo = new Memory();

		$repo->save( 'test:1', 'value1' );
		$repo->save( 'test:2', 'value2' );
		$repo->save( 'test:3', 'value3' );

		$this->assertTrue( $repo->exists( 'test:1' ) );
		$this->assertTrue( $repo->exists( 'test:2' ) );
		$this->assertTrue( $repo->exists( 'test:3' ) );

		$repo->clear();

		$this->assertFalse( $repo->exists( 'test:1' ) );
		$this->assertFalse( $repo->exists( 'test:2' ) );
		$this->assertFalse( $repo->exists( 'test:3' ) );
	}

	public function testKeys()
	{
		$repo = new Memory();

		$repo->save( 'test:1', 'value1' );
		$repo->save( 'test:2', 'value2' );
		$repo->save( 'test:3', 'value3' );

		$keys = $repo->keys();

		$this->assertIsArray( $keys );
		$this->assertCount( 3, $keys );
		$this->assertContains( 'test:1', $keys );
		$this->assertContains( 'test:2', $keys );
		$this->assertContains( 'test:3', $keys );
	}
}
