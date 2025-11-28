<?php

use Neuron\Data\Date;

class DateTest extends PHPUnit\Framework\TestCase
{
	public function testWeekDateRange()
	{
		$Range = Date::getDateRangeForWeek( 1, 2020 );

		$this->assertEquals(
			"2019-12-30",
			$Range->start
		);

		$this->assertEquals(
			"2020-01-05",
			$Range->end
		);

		$this->assertIsObject( Date::getDateRangeForWeek() );

	}

	public function testMonthDateRange()
	{
		$Range = Date::getDateRangeForMonth( 1, 2020 );

		$this->assertEquals(
			"2020-01-01",
			$Range->start
		);

		$this->assertEquals(
			"2020-01-31",
			$Range->end
		);

		$Range = Date::getDateRangeForMonth();

		$this->assertIsObject( $Range );
	}

	public function testQuarterDateRange()
	{
		$Q = Date::getDateRangeForQuarter( 0, '2010' );

		$Current = date( 'm' );
		$Month = 0;
		if( $Current < 4 )
		{
			$Month = "01";
		}
		else if( $Current >= 4 && $Current <= 6 )
		{
			$Month = "04";
		}
		else if( $Current >= 7 && $Current <= 9)
		{
			$Month = "07";
		}
		else if( $Current > 9 )
		{
			$Month = "10";
		}

		$this->assertEquals(
			"2010-$Month-01",
			$Q->start
		);

		$Q1 = Date::getDateRangeForQuarter( 1, '2010' );

		$this->assertEquals(
			'2010-01-01',
			$Q1->start
		);

		$this->assertEquals(
			'2010-03-31',
			$Q1->end
		);

		$Q2 = Date::getDateRangeForQuarter( 2, '2010' );

		$this->assertEquals(
			'2010-04-01',
			$Q2->start
		);

		$this->assertEquals(
			'2010-06-30',
			$Q2->end
		);

		$Q3 = Date::getDateRangeForQuarter( 3, '2010' );

		$this->assertEquals(
			'2010-07-01',
			$Q3->start
		);

		$this->assertEquals(
			'2010-09-30',
			$Q3->end
		);

		$Q4 = Date::getDateRangeForQuarter( 4, '2010' );

		$this->assertEquals(
			'2010-10-01',
			$Q4->start
		);

		$this->assertEquals(
			'2010-12-31',
			$Q4->end
		);

		$Q4 = Date::getDateRangeForQuarter();

		$this->assertIsObject( $Q4 );
	}

	public function testDateOnly()
	{
		$this->assertEquals(
			Neuron\Data\Date::only( '2018-01-15 01:02:03' ),
			'2018-01-15'
		);
	}

	public function testToday()
	{
		$this->assertEquals(
			Neuron\Data\Date::today(),
			date( 'Y-m-d' )
		);
	}

	public function testTomorrow()
	{
		$this->assertEquals(
			Neuron\Data\Date::tomorrow(),
			date( 'Y-m-d', strtotime( 'tomorrow' ) )
		);
	}

	public function testYesterday()
	{
		$this->assertEquals(
			Neuron\Data\Date::yesterday(),
			date( 'Y-m-d', strtotime( 'yesterday' ) )
		);
	}

	public function testDaysAsText()
	{
		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 1 ),
			'1 day'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 2 ),
			'2 days'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 7 ),
			'1 week'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 14 ),
			'2 weeks'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 15 ),
			'2 weeks, 1 day'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 16 ),
			'2 weeks, 2 days'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 30 ),
			'1 month'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 60 ),
			'2 months'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 61 ),
			'2 months, 1 day'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 62 ),
			'2 months, 2 days'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 68 ),
			'2 months, 1 week, 1 day'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 365 ),
			'1 year'
		);

		$this->assertEquals(
			Neuron\Data\Date::daysAsText( 365 + 60 + 14 + 2 ),
			'1 year, 2 months, 2 weeks, 2 days'
		);

	}

	public function testDifferenceUnitAsText()
	{
		// year

		$this->assertEquals(
			Neuron\Data\Date::differenceUnitAsText( 31536000, 31536000 * 2 ),
			'1 year'
		);

		// years

		$this->assertEquals(
			Neuron\Data\Date::differenceUnitAsText( 31536000, 31536000 * 3 ),
			'2 years'
		);

		// month

		$this->assertEquals(
			Neuron\Data\Date::differenceUnitAsText( 2592000, 2592000 * 2 ),
			'1 month'
		);

		// week

		$this->assertEquals(
			Neuron\Data\Date::differenceUnitAsText( 604800, 604800 * 2 ),
			'1 week'
		);

		// day

		$this->assertEquals(
			Neuron\Data\Date::differenceUnitAsText( 86400, 86400 * 2 ),
			'1 day'
		);

		// hour

		$this->assertEquals(
			Neuron\Data\Date::differenceUnitAsText( 3600, 3600 * 2 ),
			'1 hour'
		);

		// minute

		$this->assertEquals(
			Neuron\Data\Date::differenceUnitAsText( 60, 60 * 2 ),
			'1 minute'
		);

		// second

		$this->assertEquals(
			Neuron\Data\Date::differenceUnitAsText( 1, 1 * 2 ),
			'1 second'
		);

		$this->assertIsString(
			Neuron\Data\Date::differenceUnitAsText( 1 ),
		);
	}

	public function testLeapYear()
	{
		$this->assertTrue(
			Neuron\Data\Date::isLeapYear( 2004 )
		);

		$this->assertFalse(
			Neuron\Data\Date::isLeapYear( 2003 )
		);
	}

	public function testDaysInMonth()
	{
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 1 ) == 31
		);

		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 2, 2004 ) == 29
		);

		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 2, 1805 ) == 28
		);

		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 3, 1805 ) == 31
		);
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 4, 1805 ) == 30
		);
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 5, 1805 ) == 31
		);
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 6, 1805 ) == 30
		);
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 7, 1805 ) == 31
		);
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 8, 1805 ) == 31
		);
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 9, 1805 ) == 30
		);
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 10, 1805 ) == 31
		);
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 11, 1805 ) == 30
		);
		$this->assertTrue(
			Neuron\Data\Date::getDaysInMonth( 12, 1805 ) == 31
		);

	}

	public function testDiff()
	{
		$this->assertTrue(
			Neuron\Data\Date::diff( date( 'Y-m-d' ), date( 'Y-m-d', strtotime( "-30 days" ) ) ) == 30
		);
	}

	public function testSubtractDays()
	{
		$this->assertTrue(
			Neuron\Data\Date::subtractDays( 8, '2015-01-30' ) == '2015-01-22'
		);

		$this->assertFalse(
			Neuron\Data\Date::subtractDays( 8, '2015-01-30' ) == '2015-01-21'
		);

		$this->assertIsString(
			Neuron\Data\Date::subtractDays( 15 )
		);

	}

	public function testGetCurrentMonthStartDate()
	{
		$this->assertEquals(
			date( 'Y-m-01' ),
			Neuron\Data\Date::getCurrentMonthStartDate(),
		);
	}

	public function testGetCurrentMonthEndDate()
	{
		$Date = Neuron\Data\Date::getCurrentMonthEndDate();

		$this->assertIsString( $Date );
	}

	public function testDiffDateTime()
	{
		$this->assertEquals(
			0,
			\Neuron\Data\Date::compare(
				'2020-03-27 12:00:00',
				'2020-03-27 12:00:00'
			)
		);

		$this->assertEquals(
			-1,
			\Neuron\Data\Date::compare(
				'2020-03-27 11:00:00',
				'2020-03-27 12:00:00'
			)
		);

		$this->assertEquals(
			1,
			\Neuron\Data\Date::compare(
				'2020-03-27 13:00:00',
				'2020-03-27 12:00:00'
			)
		);
	}

	public function testGetDay()
	{
		$this->assertEquals(
			Date::getDay( '2019-06-28' ),
			'Friday'
		);
	}

	public function testIsWeekend()
	{
		$this->assertTrue(
			Date::isWeekend( '2019-06-29' )
		);

		$this->assertTrue(
			Date::isWeekend( '2019-06-30' )
		);

		$this->assertFalse(
			Date::isWeekend( '2019-06-28' )
		);
	}

	public function testWorkingDays()
	{
		$this->assertEquals(
			Date::getWorkingDays(
				new \Neuron\Data\Objects\DateRange( '2019-06-01', '2019-06-30' )
			),
			20
		);
	}
}
