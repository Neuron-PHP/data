<?php
namespace Neuron\Data\Filters;

/**
 * Filters SERVER data.
 */
class Server implements IFilter
{
	/**
	 * @param string $data
	 * @param mixed|null $default
	 * @return mixed
	 */

	public static function filterScalar( string $data, mixed $default = null ): mixed
	{
		$value = filter_input( INPUT_SERVER, $data );
		return $value !== null ? $value : $default;
	}

	/**
	 * @param array $data
	 * @return array|false|null
	 */

	public static function filterArray( array $data ): false|array|null
	{
		return filter_input_array(INPUT_SERVER, $data,FILTER_DEFAULT );
	}
}
