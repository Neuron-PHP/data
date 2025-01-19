<?php

namespace Neuron\Data\Filter;

/**
 * Filters cookies.
 */
class Cookie implements IFilter
{
	/**
	 * @param string $Data
	 * @return mixed
	 */
	public static function filterScalar( $Data ) : mixed
	{
		return filter_input(INPUT_COOKIE, $Data );
	}

	/**
	 * @param array $Data
	 * @return array|false|null
	 */
	public static function filterArray( array $Data ) : array|false|null
	{
		return filter_input_array(INPUT_COOKIE, $Data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
	}
}
