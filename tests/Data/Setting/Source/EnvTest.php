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
}
