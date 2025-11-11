<?php

namespace Neuron\Data\Object;

use Neuron\Data\Formatter;
use Neuron\Data\Date;

/**
 * Object for holding date ranges.
 */
class DateRange
{
	public string $start;
	public string $end;

	/**
	 * @param string $start
	 * @param string $end
	 */

	public function __construct( string $start, string $end )
	{
		$date = new \Neuron\Formatters\Date();
		$this->start = $date->format( $start );
		$this->end   = $date->format( $end );
	}

	/**
	 * Returns the number of days between start and end.
	 * @return int
	 */

	public function getLengthInDays() : int
	{
		return Date::diff( $this->end, $this->start );
	}
}
