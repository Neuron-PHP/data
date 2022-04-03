<?php

namespace Neuron\Data\Object;

use Neuron\Data\Formatter;
use Neuron\Data\Date;

/**
 * Object for holding date ranges.
 */
class DateRange
{
	public $Start;
	public $End;

	public function __construct( $Start, $End )
	{
		$Date = new \Neuron\Formatters\Date();
		$this->Start = $Date->format( $Start );
		$this->End   = $Date->format( $End );
	}

	/**
	 * Returns the number of days between start and end.
	 * @return int
	 */
	public function getLengthInDays() : int
	{
		return Date::diff( $this->End, $this->Start );
	}
}
