<?php

namespace Neuron\Data\Filters;

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

		// Fallback to $_GET for PHP built-in server compatibility
		// filter_input() reads from original input buffer which doesn't see runtime $_GET modifications
		if( $value === null || $value === false )
		{
			$value = $_GET[ $data ] ?? null;
		}

		return ($value !== null && $value !== false) ? $value : $default;
	}

	/**
	 * @param array $data
	 * @return array|false|null
	 */

	public static function filterArray( array $data ) : array | false | null
	{
		return filter_input_array(INPUT_GET, $data,FILTER_DEFAULT );
	}
}
