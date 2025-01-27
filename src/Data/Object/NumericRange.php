<?php

namespace Neuron\Data\Object;

/**
 * Object for holding numeric ranges.
 */
class NumericRange
{
	public int | float $Minimum;
	public int | float $Maximum;

	/**
	 * @param int|float $Minimum
	 * @param int|float $Maximum
	 */

	public function __construct( int | float $Minimum, int | float $Maximum )
	{
		$this->Minimum = $Minimum;
		$this->Maximum = $Maximum;
	}
}
