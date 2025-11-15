<?php

namespace Neuron\Data\Filter;

/**
 * Filters post data
 */
class Post implements IFilter
{
	/**
	 * @param string $data
	 * @param mixed|null $default
	 * @return mixed
	 */

	public static function filterScalar( string $data, mixed $default = null ): mixed
	{
		$value = filter_input( INPUT_POST, $data );
		return $value !== null ? $value : $default;
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
