<?php

namespace Neuron\Data\Filters;

/**
 * Filters cookies.
 */
class Cookie implements IFilter
{
	/**
	 * @param string $data
	 * @param mixed|null $default
	 * @return mixed
	 */

	public static function filterScalar( string $data, mixed $default = null ) : mixed
	{
		$value = filter_input( INPUT_COOKIE, $data );
		return ($value !== null && $value !== false) ? $value : $default;
	}

	/**
	 * @param array $data
	 * @return array|false|null
	 */
	public static function filterArray( array $data ) : array | false | null
	{
		return filter_input_array(INPUT_COOKIE, $data,FILTER_DEFAULT );
	}
}
