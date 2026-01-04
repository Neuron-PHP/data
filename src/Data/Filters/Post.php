<?php

namespace Neuron\Data\Filters;

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
		// Fallback to $_POST for PHP built-in server compatibility
		if( $value === null || $value === false )
		{
			$value = $_POST[ $data ] ?? null;
		}
		return ($value !== null && $value !== false) ? $value : $default;
	}

	/**
	 * @param array $data
	 * @return false|array|null
	 */

	public static function filterArray( array $data ): false|array|null
	{
		return filter_input_array(INPUT_POST, $data,FILTER_DEFAULT );
	}
}
