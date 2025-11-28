<?php

use Neuron\Data\Parsers;
use PHPUnit\Framework\TestCase;

class FirstMITest extends PHPUnit\Framework\TestCase
{

	public function testParse()
	{
		$Parser = new Parsers\FirstMI();

		list( $First, $Middle ) = $Parser->parse( "Alfred E" );

		$this->assertEquals( $First,	'Alfred' );
		$this->assertEquals( $Middle,	'E' );
	}

	public function testParseWithPeriod()
	{
		$Parser = new Parsers\FirstMI();

		list( $First, $Middle ) = $Parser->parse( "Alfred E." );

		$this->assertEquals( $First,	'Alfred' );
		$this->assertEquals( $Middle,	'E' );
	}

	public function testFirstOnly()
	{
		$Parser = new Parsers\FirstMI();

		list( $First, $Middle ) = $Parser->parse( "Alfred" );

		$this->assertEquals( $First,	'Alfred' );
		$this->assertEquals( $Middle,	'' );
	}
}
