<?php

use Neuron\Data\ArrayHelper;

class ArrayHelperTest extends PHPUnit\Framework\TestCase
{
	public function testContains()
	{
		$Test = [
			'one',
			'two',
			'three'
		];

		// sad
		$this->assertFalse( ArrayHelper::contains( $Test, 'twenty' ) );

		// happy
		$this->assertTrue( ArrayHelper::contains( $Test, 'two' ) );
	}

	public function testContainsKeyValue()
	{
		$Test = [
			'one' => 1,
			'two' => 2,
			'three' => 3
		];

		// sad
		$this->assertFalse( ArrayHelper::contains( $Test, 'four' ) );

		// happy
		$this->assertTrue( ArrayHelper::contains( $Test, 2 ) );

		// sad
		$this->assertFalse( ArrayHelper::contains( $Test, 1, 'four' ) );

		// happy
		$this->assertTrue( ArrayHelper::contains( $Test, 2, 'two' ) );
	}

	public function testHasKey()
	{
		$Test = [
			'one' => 1,
			'two' => 2,
			'three' => 3
		];

		// sad
		$this->assertFalse( ArrayHelper::hasKey( $Test, 'four' ) );

		// happy
		$this->assertTrue( ArrayHelper::hasKey( $Test, 'two' ) );

		// sad
		$this->assertFalse( ArrayHelper::hasKey( $Test, 'four', 1 ) );

		// happy
		$this->assertTrue( ArrayHelper::hasKey( $Test, 'two', 2 ) );
	}

	public function testGetElement()
	{
		$Test = [
			'one'   => 1,
			'two'   => 2,
			'three' => 3
		];

		// sad
		$this->assertEquals( null, ArrayHelper::getElement( $Test, 'five' ) );

		// happy
		$this->assertEquals( 1,    ArrayHelper::getElement( $Test, 'one' ) );
		$this->assertEquals( 20,   ArrayHelper::getElement( $Test, 'five', 20 ) );
	}

	public function testIndexOf()
	{
		$Test = [
			'one',
			'two',
			'three'
		];

		// sad
		$this->assertEquals( false, ArrayHelper::indexOf( $Test, 'twelve' ) );

		// happy
		$this->assertEquals( 1, ArrayHelper::indexOf( $Test, 'two' ) );
	}

	public function testRemove()
	{
		$Test = [
			'one',
			'two',
			'three'
		];

		// sad
		$this->assertEquals( false, ArrayHelper::remove( $Test, 'twelve' ) );

		// happy
		$this->assertEquals( true, ArrayHelper::remove( $Test, 'two' ) );
		$this->assertEquals( false, ArrayHelper::contains( $Test, 'two' ) );
	}
}
