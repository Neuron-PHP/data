<?php
namespace Neuron\Data\Filter;

/**
 * Filters SERVER data.
 */
class Server implements IFilter
{
	/**
	 * @param $data
	 * @return mixed
	 */

	public static function filterScalar( $data ): mixed
	{
		return filter_input( INPUT_SERVER, $data );
	}

	/**
	 * @param array $data
	 * @return array|false|null
	 */

	public static function filterArray( array $data ): false|array|null
	{
		return filter_input_array(INPUT_SERVER, $data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}
}
