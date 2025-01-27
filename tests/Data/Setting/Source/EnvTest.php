<?php

namespace Data\Setting\Source;

use Neuron\Data\Setting\Source\Env;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
	public function testGet()
	{
		$Env = new Env( \Neuron\Data\Env::getInstance() );

		$Value = $Env->get( 'test', 'name' );

		$this->assertEquals(
			'value',
			$Value
		);
	}

	public function testGetFail()
	{
		$Env = new Env( \Neuron\Data\Env::getInstance() );

		$Value = $Env->get( 'test', 'name2' );

		$this->assertNull( $Value );
	}

	public function testSet()
	{
		$Env = new Env( \Neuron\Data\Env::getInstance() );

		$Env->set( 'test', 'name', 'value' );

		$Value = $Env->get( 'test', 'name' );

		$this->assertEquals(
			'value',
			$Value
		);
	}

	public function testSave()
	{
		$Env = new Env( \Neuron\Data\Env::getInstance() );

		$this->assertFalse( $Env->save() );
	}

	public function testGetSectionNames()
	{
		$Env = new Env( \Neuron\Data\Env::getInstance() );

		$Sections = $Env->getSectionNames();

		$this->assertIsArray( $Sections );
	}

	public function testGetSectionSettingNames()
	{
		$Env = new Env( \Neuron\Data\Env::getInstance() );

		$Sections = $Env->getSectionSettingNames( 'test' );

		$this->assertIsArray( $Sections );
	}

}
