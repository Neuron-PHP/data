<?php

namespace Neuron\Data\Object;

/**
 * Object for holding numeric ranges.
 */
class NumericRange
{
	public int | float $minimum;
	public int | float $maximum;

	/**
	 * @param int|float $minimum
	 * @param int|float $maximum
	 */

	public function __construct( int | float $minimum, int | float $maximum )
	{
		$this->minimum = $minimum;
		$this->maximum = $maximum;
	}
}
