<?php

use Neuron\Data\Objects\Version;
use Neuron\Data\Factories;

class VersionFactoryTest extends PHPUnit\Framework\TestCase
{
	public function testFromStringWithoutBuild()
	{
		$Version = Factories\Version::fromString(
			'{"major":1,"minor":2,"patch":3}'
		);

		$this->assertInstanceOf( Version::class, $Version );
		$this->assertEquals( 1, $Version->major );
		$this->assertEquals( 2, $Version->minor );
		$this->assertEquals( 3, $Version->patch );
		$this->assertEquals( 0, $Version->build );
	}

	public function testFromStringWithBuild()
	{
		$Version = Factories\Version::fromString(
			'{"major":1,"minor":2,"patch":3,"build":4}'
		);

		$this->assertInstanceOf( Version::class, $Version );
		$this->assertEquals( 1, $Version->major );
		$this->assertEquals( 2, $Version->minor );
		$this->assertEquals( 3, $Version->patch );
		$this->assertEquals( 4, $Version->build );
	}

	public function testFromStringWithInvalidJson()
	{
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Unable to parse json from 'meh'" );

		Factories\Version::fromString( 'meh' );
	}

	public function testFromFile()
	{
		$Version = Factories\Version::fromFile( 'examples/version.json' );

		$this->assertInstanceOf( Version::class, $Version );
		$this->assertEquals( 1, $Version->major );
		$this->assertEquals( 2, $Version->minor );
		$this->assertEquals( 3, $Version->patch );
		$this->assertEquals( 0, $Version->build );
	}

	public function testFromFileNotFound()
	{
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "Cannot find version file 'DoesNotExist'" );

		Factories\Version::fromFile( 'DoesNotExist' );
	}

	public function testFromStringReturnsCorrectStringRepresentation()
	{
		$Version = Factories\Version::fromString(
			'{"major":5,"minor":10,"patch":15,"build":20}'
		);

		$this->assertEquals( '5.10.15 (20)', $Version->getAsString() );
	}
}
