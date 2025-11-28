<?php
namespace Data\Setting\Source;

use Neuron\Data\Settings\Source\Yaml;
use PHPUnit\Framework\TestCase;

class YamlTest extends TestCase
{
	public Yaml $Yaml;

	protected function setUp(): void
	{
		$this->Yaml = new Yaml( 'examples/test.yaml' );
		parent::setUp();
	}

	public function testNotExists()
	{
		$Pass = false;
		try
		{
			$Ini = new Yaml( 'examples/notexists.yaml' );
		}
		catch( \Exception $e )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testCantParse()
	{
		$Pass = false;
		try
		{
			$Ini = new Yaml( 'examples/bad.yaml' );
		}
		catch( \Exception $e )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testGet()
	{
		$Yaml = new Yaml( 'examples/test.yaml' );

		$Value = $Yaml->get( 'test', 'name' );

		$this->assertEquals(
			'value',
			$Value
		);
	}

	public function testGetFail()
	{
		$Yaml = new Yaml( 'examples/test.yaml' );

		$Value = $Yaml->get( 'test', 'doesntexist' );

		$this->assertNull( $Value );
	}

	public function testSet()
	{
		$this->Yaml->set( 'test', 'newname', 'value' );

		$Value = $this->Yaml->get( 'test', 'newname' );

		$this->assertEquals( 'value', $Value );
	}


	public function testGetSectionNames()
	{
		$Sections = $this->Yaml->getSectionNames();

		$this->assertEquals( 'test', $Sections[ 0 ] );
	}

	public function testGetSectionSettingNames()
	{
		$Names = $this->Yaml->getSectionSettingNames( 'test' );

		$this->assertEquals( 'name', $Names[ 0 ] );
	}

	public function testSave()
	{
		$this->assertFalse( $this->Yaml->save() );
	}

}
