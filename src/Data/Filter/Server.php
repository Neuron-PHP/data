<?php
namespace Neuron\Data\Filter;

/**
 * Filters SERVER data.
 */
class Server implements IFilter
{
	/**
	 * @param $Data
	 * @return mixed
	 */

	public static function filterScalar( $Data ): mixed
	{
		return filter_input( INPUT_SERVER, $Data );
	}

	/**
	 * @param array $Data
	 * @return array|false|null
	 */

	public static function filterArray( array $Data ): false|array|null
	{
		return filter_input_array(INPUT_SERVER, $Data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}
}
