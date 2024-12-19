<?php

namespace Data\Setting;

use Neuron\Data\Setting\Source\Ini;
use PHPUnit\Framework\TestCase;

class SettingManagerTest extends TestCase
{
	public function testGetSetting()
	{
		$Source = new Ini( 'examples/test.ini' );

		$Value = $Source->get( 'test', 'name' );

		$this->assertEquals( 'value', $Value );
	}

	public function testSetSetting()
	{
		$Source = new Ini( 'examples/test.ini' );

		$Source->set( 'test', 'newname', 'value' );

		$Value = $Source->get( 'test', 'newname' );

		$this->assertEquals( 'value', $Value );
	}
}
