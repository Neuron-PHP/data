<?php

namespace Neuron\Data\Object;

/**
 * Object for holding numeric ranges.
 */
class NumericRange
{
	public $Minimum;
	public $Maximum;

	public function __construct( $Minimum, $Maximum )
	{
		$this->Minimum = $Minimum;
		$this->Maximum = $Maximum;
	}
}
