<?php

namespace Data\Setting;

use Neuron\Data\Setting\SettingManager;
use Neuron\Data\Setting\Source;
use Neuron\Data;
use PHPUnit\Framework\TestCase;

class SettingManagerTest extends TestCase
{
	public function testGetSetting()
	{
		$Source = new Source\Ini( 'examples/test.ini' );

		$Value = $Source->get( 'test', 'name' );

		$this->assertEquals( 'value', $Value );
	}

	public function testSetSetting()
	{
		$Source = new Source\Ini( 'examples/test.ini' );

		$Source->set( 'test', 'newname', 'value' );

		$Value = $Source->get( 'test', 'newname' );

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
}
