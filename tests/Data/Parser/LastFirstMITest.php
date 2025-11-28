<?php

use Neuron\Data\Parsers;

class LastFirstMITest extends PHPUnit\Framework\TestCase
{
	public function testPass()
	{
		$Parser = new Parsers\LastFirstMI();


		list( $sFirst, $sMiddle, $sLast ) = $Parser->parse( "Newman, Alfred E" );

		$this->assertEquals( $sFirst,		'Alfred' );
		$this->assertEquals( $sMiddle,	'E' );
		$this->assertEquals( $sLast,		'Newman' );
	}
}
