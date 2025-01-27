<?php

namespace Data\Setting\Source;

use Neuron\Data\Setting\Source\Ini;
use PHPUnit\Framework\TestCase;

class IniTest extends TestCase
{
	public Ini $Ini;

	protected function setUp(): void
	{
		$this->Ini = new Ini( 'examples/test.ini' );
		parent::setUp();
	}

	public function testNotExists()
	{
		$Pass = false;
		try
		{
			$Ini = new Ini( 'examples/notexists.ini' );
		}
		catch( \Exception $e )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testGetSectionNames()
	{
		$Sections = $this->Ini->getSectionNames();

		$this->assertEquals( 'test', $Sections[ 0 ] );
	}

	public function testGetSectionSettingNames()
	{
		$Names = $this->Ini->getSectionSettingNames( 'test' );

		$this->assertEquals( 'name', $Names[ 0 ] );
	}

	public function testSave()
	{
		$this->assertFalse( $this->Ini->save() );
	}

	public function testSet()
	{
		$this->Ini->set( 'test', 'newname', 'value' );

		$Value = $this->Ini->get( 'test', 'newname' );

		$this->assertEquals( 'value', $Value );
	}
}
