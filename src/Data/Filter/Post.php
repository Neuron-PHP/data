<?php

namespace Neuron\Data\Filter;

/**
 * Filters post data
 */
class Post implements IFilter
{
	/**
	 * @param $Data
	 * @return mixed
	 */

	public static function filterScalar( $Data ): mixed
	{
		return filter_input(INPUT_POST, $Data );
	}

	/**
	 * @param array $Data
	 * @return false|array|null
	 */

	public static function filterArray( array $Data ): false|array|null
	{
		return filter_input_array(INPUT_POST, $Data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}
}
