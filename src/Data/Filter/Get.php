<?php

namespace Neuron\Data\Filter;

/**
 * Filters get data.
 */
class Get implements IFilter
{

	/**
	 * @param string $data
	 * @param mixed|null $default
	 * @return mixed
	 */

	public static function filterScalar( string $data, mixed $default = null ) : mixed
	{
		$value = filter_input( INPUT_GET, $data );
		return ($value !== null && $value !== false) ? $value : $default;
	}

	/**
	 * @param array $data
	 * @return array|false|null
	 */

	public static function filterArray( array $data ) : array | false | null
	{
		return filter_input_array(INPUT_GET, $data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}
}
