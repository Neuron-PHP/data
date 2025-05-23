<?php

use Neuron\Data\Object\GpsPoint;

class GpsPointTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$Point = new GpsPoint( 1.0, 2.0 );

		$this->assertEquals( $Point->Latitude,  1.0 );
		$this->assertEquals( $Point->Longitude, 2.0 );
	}
}
