<?php


use Neuron\Data\Objects\NumericRange;
use PHPUnit\Framework\TestCase;

class NumericRangeTest extends TestCase
{
	public function testConstruct()
	{
		$Range = new NumericRange( 1.0, 2.0 );

		$this->assertEquals( $Range->minimum,  1.0 );
		$this->assertEquals( $Range->maximum, 2.0 );
	}
}
