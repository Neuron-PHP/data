<?php

namespace Data\Setting\Source;

use Neuron\Data\Settings\Source\Env;
use Neuron\Data\Settings\Source\Memory;
use PHPUnit\Framework\TestCase;

class MemoryTest extends TestCase
{
	public function testGetFail()
	{
		$Memory = new Memory();

		$Value = $Memory->get( 'test', 'name2' );

		$this->assertNull( $Value );
	}

	public function testSet()
	{
		$Memory = new Memory();

		$Memory->set( 'test', 'name', 'value' );

		$Value = $Memory->get( 'test', 'name' );

		$this->assertEquals(
			'value',
			$Value
		);
	}

	public function testSave()
	{
		$Memory = new Memory();

		$this->assertFalse( $Memory->save() );
	}

	public function testGetSectionNames()
	{
		$Memory = new Memory();

		$Sections = $Memory->getSectionNames();

		$this->assertIsArray( $Sections );
	}

	public function testGetSectionSettingNames()
	{
		$Memory = new Memory();
		$Memory->set( 'test', 'name', 'value' );

		$Sections = $Memory->getSectionSettingNames( 'test' );

		$this->assertIsArray( $Sections );
	}
}
