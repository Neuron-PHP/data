<?php

namespace Neuron\Data\Filter;

/**
 * Filters cookies.
 */
class Cookie implements IFilter
{
	/**
	 * @param string $data
	 * @return mixed
	 */

	public static function filterScalar( $data ) : mixed
	{
		return filter_input(INPUT_COOKIE, $data );
	}

	/**
	 * @param array $data
	 * @return array|false|null
	 */

	public static function filterArray( array $data ) : array | false | null
	{
		return filter_input_array(INPUT_COOKIE, $data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
	}
}
