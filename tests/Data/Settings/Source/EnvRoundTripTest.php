<?php

namespace Tests\Data\Settings\Source;

use Neuron\Data\Env;
use Neuron\Data\Settings\Source\Env as EnvSource;
use PHPUnit\Framework\TestCase;

class EnvRoundTripTest extends TestCase
{
	private EnvSource $envSource;

	protected function setUp(): void
	{
		parent::setUp();
		// Reset the singleton to ensure clean state
		Env::getInstance()->reset();
		$this->envSource = new EnvSource( Env::getInstance() );
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		// Clean up environment variables
		putenv( 'TEST_BOOL_TRUE' );
		putenv( 'TEST_BOOL_FALSE' );
		putenv( 'TEST_NULL' );
		putenv( 'TEST_STRING' );
		putenv( 'TEST_ARRAY' );
		putenv( 'TEST_EMPTY_STRING' );
		putenv( 'TEST_NUMBER' );
	}

	/**
	 * Test that boolean true round-trips correctly
	 */
	public function testBooleanTrueRoundTrip(): void
	{
		$this->envSource->set( 'test', 'bool_true', true );
		$result = $this->envSource->get( 'test', 'bool_true' );

		$this->assertIsBool( $result );
		$this->assertTrue( $result );
	}

	/**
	 * Test that boolean false round-trips correctly
	 */
	public function testBooleanFalseRoundTrip(): void
	{
		$this->envSource->set( 'test', 'bool_false', false );
		$result = $this->envSource->get( 'test', 'bool_false' );

		$this->assertIsBool( $result );
		$this->assertFalse( $result );
	}

	/**
	 * Test that null round-trips correctly
	 */
	public function testNullRoundTrip(): void
	{
		$this->envSource->set( 'test', 'null', null );
		$result = $this->envSource->get( 'test', 'null' );

		$this->assertNull( $result );
	}

	/**
	 * Test that arrays round-trip correctly via JSON
	 */
	public function testArrayRoundTrip(): void
	{
		$array = [ 'one', 'two', 'three' ];
		$this->envSource->set( 'test', 'array', $array );
		$result = $this->envSource->get( 'test', 'array' );

		$this->assertIsArray( $result );
		$this->assertEquals( $array, $result );
	}

	/**
	 * Test that associative arrays round-trip correctly via JSON
	 */
	public function testAssociativeArrayRoundTrip(): void
	{
		$array = [ 'key1' => 'value1', 'key2' => 'value2' ];
		$this->envSource->set( 'test', 'array', $array );
		$result = $this->envSource->get( 'test', 'array' );

		$this->assertIsArray( $result );
		$this->assertEquals( $array, $result );
	}

	/**
	 * Test that regular strings round-trip correctly
	 */
	public function testStringRoundTrip(): void
	{
		$string = 'regular string value';
		$this->envSource->set( 'test', 'string', $string );
		$result = $this->envSource->get( 'test', 'string' );

		$this->assertIsString( $result );
		$this->assertEquals( $string, $result );
	}

	/**
	 * Test that numbers round-trip correctly as strings
	 * (Environment variables are always strings, so numbers become strings)
	 */
	public function testNumberRoundTrip(): void
	{
		$number = 42;
		$this->envSource->set( 'test', 'number', $number );
		$result = $this->envSource->get( 'test', 'number' );

		// Numbers are stored as strings in environment variables
		$this->assertIsString( $result );
		$this->assertEquals( '42', $result );
	}

	/**
	 * Test edge case: string 'true' should still return boolean true
	 */
	public function testStringTrueBecomesBoolean(): void
	{
		// Manually set environment variable to 'true' string
		putenv( 'TEST_MANUAL=true' );
		$result = $this->envSource->get( 'test', 'manual' );

		$this->assertIsBool( $result );
		$this->assertTrue( $result );
	}

	/**
	 * Test edge case: string 'false' should still return boolean false
	 */
	public function testStringFalseBecomesBoolean(): void
	{
		// Manually set environment variable to 'false' string
		putenv( 'TEST_MANUAL=false' );
		$result = $this->envSource->get( 'test', 'manual' );

		$this->assertIsBool( $result );
		$this->assertFalse( $result );
	}

	/**
	 * Test edge case: truly empty string in environment becomes null
	 */
	public function testEmptyStringBecomesNull(): void
	{
		// Manually set environment variable to empty string
		putenv( 'TEST_EMPTY=' );
		$result = $this->envSource->get( 'test', 'empty' );

		$this->assertNull( $result );
	}

	/**
	 * Test that comma-separated values still parse as arrays
	 */
	public function testCommaSeparatedValues(): void
	{
		// Manually set a comma-separated value
		putenv( 'TEST_CSV=value1,value2,value3' );
		$result = $this->envSource->get( 'test', 'csv' );

		$this->assertIsArray( $result );
		$this->assertEquals( [ 'value1', 'value2', 'value3' ], $result );
	}

	/**
	 * Test that the special strings 'true' and 'false' in arrays are preserved
	 */
	public function testBooleanStringsInArrays(): void
	{
		$array = [ 'true', 'false', 'other' ];
		$this->envSource->set( 'test', 'array', $array );
		$result = $this->envSource->get( 'test', 'array' );

		$this->assertIsArray( $result );
		// These should remain as strings when part of an array
		$this->assertEquals( [ 'true', 'false', 'other' ], $result );
	}
}