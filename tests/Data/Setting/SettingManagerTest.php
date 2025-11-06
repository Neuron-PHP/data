<?php

namespace Data\Setting;

use Neuron\Data\Setting\SettingManager;
use Neuron\Data\Setting\Source;
use Neuron\Data;
use PHPUnit\Framework\TestCase;

class SettingManagerTest extends TestCase
{
	public SettingManager $Manager;

	protected function setUp(): void
	{
		$this->Manager = new SettingManager(
			new Source\Ini( 'examples/test.ini' )
		);

		parent::setUp();
	}

	public function testGetSetting()
	{
		$Value = $this->Manager->get( 'test', 'name' );

		$this->assertEquals( 'value', $Value );
	}

	public function testSetSetting()
	{
		$this->Manager->set( 'test', 'newname', 'value' );

		$Value = $this->Manager->get( 'test', 'newname' );

		$this->assertEquals( 'value', $Value );
	}

	public function testFallback()
	{
		$Source = new Source\Ini( 'examples/test.ini' );
		$Fallback = new Source\Env( Data\Env::getInstance('examples/.env' ) );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( $Fallback );

		$Value = $Manager->get( 'test', 'not_there' );

		$this->assertEquals( 'no', $Value );
	}

	public function testGetSectionNames()
	{
		$Sections = $this->Manager->getSectionNames();

		$this->assertEquals( 'test', $Sections[ 0 ] );
	}

	public function testGetSectionSettingNames()
	{
		$Names = $this->Manager->getSectionSettingNames( 'test' );

		$this->assertEquals( 'name', $Names[ 0 ] );
	}

	public function testGetSection()
	{
		$Section = $this->Manager->getSection( 'test' );

		$this->assertIsArray( $Section );
		$this->assertArrayHasKey( 'name', $Section );
		$this->assertEquals( 'value', $Section[ 'name' ] );
	}

	public function testGetSectionNotFound()
	{
		$Section = $this->Manager->getSection( 'nonexistent' );

		$this->assertNull( $Section );
	}

	public function testGetSectionWithFallback()
	{
		$Source = new Source\Ini( 'examples/test.ini' );
		$Fallback = new Source\Env( Data\Env::getInstance('examples/.env' ) );

		$Manager = new SettingManager( $Source );
		$Manager->setFallback( $Fallback );

		// Test getting section from fallback when not in primary source
		$Section = $Manager->getSection( 'test' );

		$this->assertIsArray( $Section );
		$this->assertArrayHasKey( 'name', $Section );
	}
}
