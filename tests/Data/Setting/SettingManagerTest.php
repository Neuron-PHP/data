<?php

use Neuron\Data\Setting\SettingManager;
use PHPUnit\Framework\TestCase;

class SettingManagerTest extends TestCase
{
	public function testSettings()
	{
		$Source = new \Neuron\Data\Setting\Source\Ini( 'examples/test.ini' );

		$Value = $Source->get( 'test', 'name' );

		$this->assertEquals(
			'value',
			$Value
		);
	}

	public function testSetSetting()
	{
		$Source = new \Neuron\Data\Setting\Source\Ini( 'examples/test.ini' );

		$Source->set( 'test', 'newname',  'value' );

		$Value = $Source->get( 'test', 'newname' );

		$this->assertEquals(
			'value',
			$Value
		);
	}

}
