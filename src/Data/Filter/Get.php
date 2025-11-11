<?php

namespace Neuron\Data\Filter;

/**
 * Filters get data.
 */
class Get implements IFilter
{

	/**
	 * @param $data
	 * @return mixed
	 */

	public static function filterScalar( $data ) : mixed
	{
		return filter_input( INPUT_GET, $data );
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
