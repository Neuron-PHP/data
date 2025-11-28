<?php

namespace Neuron\Data;

use Neuron\Data\Objects\DateRange;
use Neuron\Util;

/**
 * General date functions.
 */
class Date
{
	private static array $quarters = [
		1 => [
			"01-01",
			"03-31"
		],
		2 => [
			"04-01",
			"06-30"
		],
		3 => [
			"07-01",
			"09-30"
		],
		4 => [
			"10-01",
			"12-31"
		]
	];

	/**
	 * Returns the date range for the specified fiscal quarter.
	 *
	 * @param int $quarter
	 * @param string $year
	 * @return DateRange
	 */

	static function getDateRangeForQuarter( int $quarter = 0, string $year = '' ) : DateRange
	{
		if( !$quarter )
		{
			$month   = date( 'm' );
			$quarter = ceil( $month / 3 );
		}

		if( !$year )
		{
			$year = date( 'Y' );
		}

		return new DateRange(
			$year.'-'.self::$quarters[ $quarter ][ 0 ],
			$year.'-'.self::$quarters[ $quarter ][ 1 ]
		);
	}

	/**
	 * Returns the date range for a year/month number.
	 *
	 * @param int $month
	 * @param string $year
	 * @return DateRange
	 */

	static function getDateRangeForMonth( int $month = 0, string $year = '' ) : DateRange
	{
		if( !$month )
		{
			$month = date( 'm' );
		}

		if( !$year )
		{
			$year = date( 'Y' );
		}

		$start = "$year-$month-01";
		$end   = "$year-$month-".self::getDaysInMonth( $month, $year );

		return new DateRange(
			$start,
			$end
		);
	}

	/**
	 * Returns the date range for a specific year/week number.
	 *
	 * @param int $week
	 * @param string $year
	 * @return DateRange
	 */

	static function getDateRangeForWeek( int $week = 0, string $year = '' ) : DateRange
	{
		if( !$week )
		{
			$dayOfYear = date('z') + 1;
			$week      = ceil( $dayOfYear / 7 );
		}

		$week -= 1;

		if( !$year )
		{
			$year = date( 'Y' );
		}

		$dayOfYear = $week * 7;

		$julian = self::dateToJulian( "$year-01-01" );

		$julian += $dayOfYear;

		// If the first day isn't a Monday then back up until
		// one is found.

		$weekDay = self::getWeekday( self::julianToDate( $julian ) );

		while( $weekDay != 1 )
		{
			$julian--;
			$weekDay = self::getWeekday( self::julianToDate( $julian ) );
		}

		$julianEnd = $julian + 6;

		return new DateRange(
			self::julianToDate( $julian ),
			self::julianToDate( $julianEnd )
		);
	}

	/**
	 * Returns an int representing the day of the week.
	 * 0 = Sunday .. 6 = Saturday
	 *
	 * @param string $date
	 * @return int
	 */

	static function getWeekday( string $date ) : int
	{
		return date('w', strtotime( $date ) );
	}

	/**
	 * Return today's date
	 *
	 * @return false|string
	 */

	static function today(): bool|string
	{
		return date( 'Y-m-d' );
	}

	/**
	 * Return tomorrow's date
	 *
	 * @return false|string
	 */

	static function tomorrow(): bool|string
	{
		return date( 'Y-m-d', strtotime( 'tomorrow' ) );
	}

	/**
	 * Return yesterday's date
	 *
	 * @return false|string
	 */

	static function yesterday(): bool|string
	{
		return date( 'Y-m-d', strtotime( '-1 day' ) );
	}

	/**
	 * Get the day name for a date.
	 *
	 * @param $date
	 * @return false|string
	 */

	static function getDay( $date ): bool|string
	{
		return date('l', strtotime( $date ) );
	}

	/**
	 * Is the date on a weekend?
	 *
	 * @param $date
	 * @return bool
	 */

	static function isWeekend( $date ): bool
	{
		return ( self::getDay( $date ) == 'Saturday' ||
					self::getDay( $date ) == 'Sunday' );
	}

	/**
	 * Get the number of working days in a date range.
	 *
	 * @param DateRange $range
	 * @return int
	 */

	static function getWorkingDays( DateRange $range ): int
	{
		$days = 0;

		$start = self::dateToJulian( $range->start );
		$end   = self::dateToJulian( $range->end );

		for( $julian = $start; $julian < $end; $julian++ )
		{
			if( !self::isWeekend( self::julianToDate( $julian ) ) )
			{
				$days++;
			}
		}

		return $days;
	}

	/**
	 * If present, strips the time element from a date.
	 *
	 * @param $dateTime
	 * @return false|string
	 */

	static function only( $dateTime ): bool|string
	{
		return date( 'Y-m-d', strtotime( $dateTime ) );
	}

	/**
	 *
	 *
	 * @param $days
	 * @return string
	 */

	static function daysAsText( $days ): string
	{
		$units = [
			365 => 'year',
			30  => 'month',
			7   => 'week',
			1   => 'day'
		];

		$text = null;

		foreach( $units as $length => $unit )
		{
			if( $days >= $length )
			{
				$count = floor( $days / $length );

				if( $text )
				{
					$text .= ', ';
				}

				$text .= $count.' '.$unit;

				if( $count > 1 )
				{
					$text .= 's';
				}

				$days -= ( $count * $length );
			}
		}

		return $text;
	}

	/**
	 * Turns a time difference into a text format.
	 *
	 * @param $time
	 * @param $until
	 * @return string
	 */

	static function differenceUnitAsText( $time, $until = null ): string
	{
		if( $until == null )
		{
			$until = time();
		}

		$time = $until - $time;
		$time = ( $time < 1 ) ? 1 : $time;

		$units = [
			31536000 => 'year',
			2592000  => 'month',
			604800   => 'week',
			86400    => 'day',
			3600     => 'hour',
			60       => 'minute',
			1        => 'second'
		];

		foreach( $units as $length => $text )
		{
			if( $time < $length )
			{
				continue;
			}
			$count = floor( $time / $length );

			return $count.' '.$text.( ( $count > 1) ? 's' : '' );
		}
	}

	/**
	 * Returns true if the date ia leap year.
	 *
	 * @param $year
	 * @return bool
	 */

	static function isLeapYear( $year ): bool
	{
		return ( ( ( $year % 4 ) == 0 ) && ( ( ( $year % 100 ) != 0 ) || ( ( $year % 400 ) == 0 ) ) );
	}

	/**
	 * @param $days
	 * @param string $date
	 * @return bool|string - new date
	 */

	static function subtractDays( $days, $date = '' ): bool|string
	{
		if( !$date )
		{
			$date = date( 'Y-m-d' );
		}

		$julian  = self::dateToJulian( $date );
		$julian -= $days;

		return Date::julianToDate( $julian );
	}

	/**
	 * Returns the current month starting date.
	 *
	 * @return string A string in yyyy-mm-dd mysql format.
	 */

	static function getCurrentMonthStartDate(): string
	{
		$date  = date( "Y-m" );
		$date .= "-01";

		return $date;
	}

	/**
	 * @SuppressWarnings(PHPMD)
	 *
	 * Returns the number of days in the specified month.
	 *
	 * @param $month
	 * @param null $year
	 * @return int
	 */
	static function getDaysInMonth( $month, $year = null ): int
	{
		$days = 0;

		switch( $month )
		{
			case 2:
				if( self::isLeapYear( $year ) )
				{
					$days = 29;
				}
				else
				{
					$days = 28;
				}
				break;

			case 1:
			case 3:
			case 5:
			case 7:
			case 8:
			case 10:
			case 12:
				$days = 31;
				break;
			case 4:
			case 6:
			case 9:
			case 11:
				$days = 30;
				break;
		}

		return $days;
	}

	/**
	 * @return string
	 */
	static function getCurrentMonthEndDate(): string
	{
		$date = date( "Y-m" );

		$days = self::getDaysInMonth( date( "n" ) );

		$date .= "-" . $days;

		return $date;
	}


	//////////////////////////////////////////////////////////////////////////
	///
	/// Takes a date in dd-mm-yyyy format and returns a julian format date.
	///
	/// @param $date A string containing the date in dd-mm-yyyy format.
	///
	/// @return
	///	Julian date.
	//////////////////////////////////////////////////////////////////////////

	static function dateToJulian( $date ): int
	{
		$date = self::only( $date );

		$dateFormat = "-";

		$dateParts = explode( $dateFormat, $date );
		$startDate = gregoriantojd( $dateParts[ 1 ], $dateParts[ 2 ], $dateParts[ 0 ] );

		return $startDate;
	}

	//////////////////////////////////////////////////////////////////////////
	///
	/// Takes a date in julian format and returns it in yyyy-mm-dd format.
	///
	/// @param $date A julian date.
	///
	/// @return
	///	A string in yyyy-mm-dd mysql format.
	//////////////////////////////////////////////////////////////////////////

	static function julianToDate( $julianDate ): string
	{
		$date = jdtogregorian( $julianDate );

		$date = strtotime( $date );

		return date( "Y-m-d", $date );
	}

	static function diff( $endDate, $beginDate ) : int
	{
		$startDate = Date::dateToJulian( $beginDate );
		$endDate   = Date::dateToJulian( $endDate );

		return $endDate - $startDate;
	}

	/**
	 * @param string $first
	 * @param string $second
	 * @return int
	 */
	static function compare(  string $first, string $second ) : int
	{
		$firstTimestamp  = strtotime( $first );
		$secondTimestamp = strtotime( $second );

		return $firstTimestamp <=> $secondTimestamp;
	}
}
