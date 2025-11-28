<?php

use Neuron\Data\Objects\Version;

class VersionTest extends PHPUnit\Framework\TestCase
{
	public function testConstructor()
	{
		$Version = new Version();

		$this->assertEquals( 0, $Version->major );
		$this->assertEquals( 0, $Version->minor );
		$this->assertEquals( 0, $Version->patch );
		$this->assertEquals( 0, $Version->build );
	}

	public function testGetAsStringWithoutBuild()
	{
		$Version = new Version();
		$Version->major = 1;
		$Version->minor = 2;
		$Version->patch = 3;

		$this->assertEquals(
			'1.2.3',
			$Version->getAsString()
		);
	}

	public function testGetAsStringWithBuild()
	{
		$Version = new Version();
		$Version->major = 1;
		$Version->minor = 2;
		$Version->patch = 3;
		$Version->build = 4;

		$this->assertEquals(
			'1.2.3 (4)',
			$Version->getAsString()
		);
	}

	public function testToString()
	{
		$Version = new Version();
		$Version->major = 2;
		$Version->minor = 5;
		$Version->patch = 10;

		$this->assertEquals(
			'2.5.10',
			(string)$Version
		);
	}

	public function testToStringWithBuild()
	{
		$Version = new Version();
		$Version->major = 2;
		$Version->minor = 5;
		$Version->patch = 10;
		$Version->build = 100;

		$this->assertEquals(
			'2.5.10 (100)',
			(string)$Version
		);
	}
}
