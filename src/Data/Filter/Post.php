<?php

namespace Neuron\Data\Filter;

/**
 * Filters post data
 */
class Post implements IFilter
{
	/**
	 * @param $data
	 * @return mixed
	 */

	public static function filterScalar( $data ): mixed
	{
		return filter_input(INPUT_POST, $data );
	}

	/**
	 * @param array $data
	 * @return false|array|null
	 */

	public static function filterArray( array $data ): false|array|null
	{
		return filter_input_array(INPUT_POST, $data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}
}
