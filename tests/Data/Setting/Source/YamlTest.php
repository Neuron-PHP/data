<?php
namespace Data\Setting\Source;

use Neuron\Data\Setting\Source\Yaml;
use PHPUnit\Framework\TestCase;

class YamlTest extends TestCase
{
	public function testGet()
	{
		$Yaml = new Yaml( 'examples/test.yaml' );

		$Value = $Yaml->get( 'test', 'name' );

		$this->assertEquals(
			'value',
			$Value
		);
	}
}
