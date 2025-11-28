<?php

use Neuron\Data\Parsers;

class PositionalTest extends PHPUnit\Framework\TestCase
{
	public function testPass()
	{
		$Parser = new Parsers\Positional();

		$aRet = $Parser->parse( "text1,text2,text3,text4",
			[
				[
					'name' 	=> 'col2',
					'start'	=> 6,
					'length'	=> 5
				]
			]
		);

		$this->assertEquals( 'text2', $aRet[ 'col2' ] );
	}
}
