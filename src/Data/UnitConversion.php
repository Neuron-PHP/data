<?php

namespace Neuron\Data;

/**
 * Unit conversion functions.
 */
class UnitConversion
{
	const MILLILITERS_PER_OUNCE = 0.03381413;
	const POUNDS_PER_KILOGRAM   = 2.204623;

	/**
	 * @param float $milliliters
	 * @return float
	 */

	public static function millilitersToUsFlOunces( float $milliliters ) : float
	{
		return $milliliters * self::MILLILITERS_PER_OUNCE;
	}

	/**
	 * @param float $ounces
	 * @return float
	 */

	public static function usFlOuncesToMilliliters( float $ounces ) : float
	{
		return $ounces / self::MILLILITERS_PER_OUNCE;
	}

	/**
	 * @param float $kilograms
	 * @return float
	 */

	public static function kilogramsToPounds( float $kilograms ) : float
	{
		return $kilograms * self::POUNDS_PER_KILOGRAM;
	}

	/**
	 * @param float $pounds
	 * @return float
	 */

	public static function poundsToKilograms( float $pounds ) : float
	{
		return $pounds / self::POUNDS_PER_KILOGRAM;
	}
}
