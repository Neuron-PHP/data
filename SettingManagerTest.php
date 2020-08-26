<?php

use Neuron\Data\Setting\SettingManager;
use PHPUnit\Framework\TestCase;

class SettingManagerTest extends TestCase
{
	public function testSettings()
	{
		$Source = new \Neuron\Data\Setting\Source\Ini( 'examples/test.ini' );

		$this->_App->setSettingSource( $Source );

		$Value = $this->_App->getSetting( 'name', 'test' );

		$this->assertEquals(
			'value',
			$Value
		);
	}

	public function testSetSetting()
	{
		$Source = new \Neuron\Setting\Source\Ini( 'examples/test.ini' );

		$this->_App->setSettingSource( $Source );

		$this->_App->setSetting( 'newname', 'value',  'test' );

		$Value = $this->_App->getSetting( 'newname', 'test' );

		$this->assertEquals(
			'value',
			$Value
		);
	}

}
