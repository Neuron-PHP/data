<?php


use Neuron\Data\Object\NumericRange;
use PHPUnit\Framework\TestCase;

class NumericRangeTest extends TestCase
{
	public function testConstruct()
	{
		$Range = new NumericRange( 1.0, 2.0 );

		$this->assertEquals( $Range->Minimum,  1.0 );
		$this->assertEquals( $Range->Maximum, 2.0 );
	}
}
