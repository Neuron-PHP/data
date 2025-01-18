<?php

namespace Neuron\Data;

use Neuron\Data\Object\DateRange;
use Neuron\Util;

/**
 * General date functions.
 */
class Date
{
	private static array $_Quarters = [
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
	 * @param int $Quarter
	 * @param string $Year
	 * @return DateRange
	 */
	static function getDateRangeForQuarter( int $Quarter = 0, string $Year = '' ) : DateRange
	{
		if( !$Quarter )
		{
			$Month   = date( 'm' );
			$Quarter = ceil( $Month / 3 );
		}

		if( !$Year )
		{
			$Year = date( 'Y' );
		}

		return new DateRange(
			$Year.'-'.self::$_Quarters[ $Quarter ][ 0 ],
			$Year.'-'.self::$_Quarters[ $Quarter ][ 1 ]
		);
	}

	/**
	 * Returns the date range for a year/month number.
	 *
	 * @param int $Month
	 * @param string $Year
	 * @return DateRange
	 */
	static function getDateRangeForMonth( int $Month = 0, string $Year = '' ) : DateRange
	{
		if( !$Month )
		{
			$Month = date( 'm' );
		}

		if( !$Year )
		{
			$Year = date( 'Y' );
		}

		$Start = "$Year-$Month-01";
		$End   = "$Year-$Month-".self::getDaysInMonth( $Month, $Year );

		return new DateRange(
			$Start,
			$End
		);
	}

	/**
	 * Returns the date range for a specific year/week number.
	 *
	 * @param int $Week
	 * @param string $Year
	 * @return DateRange
	 */
	static function getDateRangeForWeek( int $Week = 0, string $Year = '' ) : DateRange
	{
		if( !$Week )
		{
			$DayOfYear = date('z') + 1;
			$Week      = ceil( $DayOfYear / 7 );
		}

		$Week -= 1;

		if( !$Year )
		{
			$Year = date( 'Y' );
		}

		$DayOfYear = $Week * 7;

		$Julian = self::dateToJulian( "$Year-01-01" );

		$Julian += $DayOfYear;

		// If the first day isn't a Monday then back up until
		// one is found.

		$WeekDay = self::getWeekday( self::julianToDate( $Julian ) );

		while( $WeekDay != 1 )
		{
			$Julian--;
			$WeekDay = self::getWeekday( self::julianToDate( $Julian ) );
		}

		$JulianEnd = $Julian + 6;

		return new DateRange(
			self::julianToDate( $Julian ),
			self::julianToDate( $JulianEnd )
		);
	}

	/**
	 * Returns an int representing the day of the week.
	 * 0 = Sunday .. 6 = Saturday
	 *
	 * @param string $Date
	 * @return int
	 */
	static function getWeekday( string $Date ) : int
	{
		return date('w', strtotime( $Date ) );
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
	 * @param $Date
	 * @return false|string
	 */
	static function getDay( $Date ): bool|string
	{
		return date('l', strtotime( $Date ) );
	}

	/**
	 * Is the date on a weekend?
	 *
	 * @param $Date
	 * @return bool
	 */
	static function isWeekend( $Date ): bool
	{
		return ( self::getDay( $Date ) == 'Saturday' ||
					self::getDay( $Date ) == 'Sunday' );
	}

	/**
	 * Get the number of working days in a date range.
	 *
	 * @param DateRange $Range
	 * @return int
	 */
	static function getWorkingDays( DateRange $Range ): int
	{
		$Days = 0;

		$Start = self::dateToJulian( $Range->Start );
		$End   = self::dateToJulian( $Range->End );

		for( $Julian = $Start; $Julian < $End; $Julian++ )
		{
			if( !self::isWeekend( self::julianToDate( $Julian ) ) )
			{
				$Days++;
			}
		}

		return $Days;
	}

	/**
	 * If present, strips the time element from a date.
	 *
	 * @param $DateTime
	 * @return false|string
	 */
	static function only( $DateTime ): bool|string
	{
		return date( 'Y-m-d', strtotime( $DateTime ) );
	}

	/**
	 *
	 *
	 * @param $Days
	 * @return mixed|string
	 */
	static function daysAsText( $Days ): mixed
	{
		$Units = [
			365 => 'year',
			30  => 'month',
			7   => 'week',
			1   => 'day'
		];

		$Text = null;

		foreach( $Units as $Length => $Unit )
		{
			if( $Days >= $Length )
			{
				$Count = floor( $Days / $Length );

				if( $Text )
				{
					$Text .= ', ';
				}

				$Text .= $Count.' '.$Unit;

				if( $Count > 1 )
				{
					$Text .= 's';
				}

				$Days -= ( $Count * $Length );
			}
		}

		return $Text;
	}

	/**
	 * Turns a time difference into a text format.
	 *
	 * @param $Time
	 * @param $Until
	 * @return string
	 */

	static function differenceUnitAsText( $Time, $Until = null ): string
	{
		if( $Until == null )
		{
			$Until = time();
		}

		$Time = $Until - $Time;
		$Time = ( $Time < 1 ) ? 1 : $Time;

		$Units = [
			31536000 => 'year',
			2592000  => 'month',
			604800   => 'week',
			86400    => 'day',
			3600     => 'hour',
			60       => 'minute',
			1        => 'second'
		];

		foreach( $Units as $Length => $Text )
		{
			if( $Time < $Length )
			{
				continue;
			}
			$Count = floor( $Time / $Length );

			return $Count.' '.$Text.( ( $Count > 1) ? 's' : '' );
		}
	}

	/**
	 * Returns true if the date ia leap year.
	 *
	 * @param $iYear
	 * @return bool
	 */

	static function isLeapYear( $iYear ): bool
	{
		return ( ( ( $iYear % 4 ) == 0 ) && ( ( ( $iYear % 100 ) != 0 ) || ( ( $iYear % 400 ) == 0 ) ) );
	}

	/**
	 * @param $iDays
	 * @param string $sDate
	 * @return bool|string - new date
	 */

	static function subtractDays( $iDays, $sDate = '' ): bool|string
	{
		if( !$sDate )
		{
			$sDate = date( 'Y-m-d' );
		}

		$julian  = self::dateToJulian( $sDate );
		$julian -= $iDays;

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
	 * @param $iMonth
	 * @param null $iYear
	 * @return int
	 */
	static function getDaysInMonth( $iMonth, $iYear = null ): int
	{
		$days = 0;

		switch( $iMonth )
		{
			case 2:
				if( self::isLeapYear( $iYear ) )
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

	static function dateToJulian( $Date ): int
	{
		$Date = self::only( $Date );

		$dformat = "-";

		$date_parts = explode( $dformat, $Date );
		$start_date = gregoriantojd( $date_parts[ 1 ], $date_parts[ 2 ], $date_parts[ 0 ] );

		return $start_date;
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

	static function julianToDate( $dt1 ): string
	{
		$date = jdtogregorian( $dt1 );

		$date = strtotime( $date );

		return date( "Y-m-d", $date );
	}

	static function diff( $endDate, $beginDate ) : int
	{
		$start_date = Date::dateToJulian( $beginDate );
		$end_date   = Date::dateToJulian( $endDate );

		return $end_date - $start_date;
	}

	/**
	 * @param string $First
	 * @param string $Second
	 * @return int
	 */
	static function compare(  string $First, string $Second ) : int
	{
		$FirstTs  = strtotime( $First );
		$SecondTs = strtotime( $Second );

		return $FirstTs <=> $SecondTs;
	}
}
