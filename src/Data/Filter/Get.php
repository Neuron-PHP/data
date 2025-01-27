<?php

namespace Neuron\Data\Filter;

/**
 * Filters get data.
 */
class Get implements IFilter
{

	/**
	 * @param $Data
	 * @return mixed
	 */

	public static function filterScalar( $Data ) : mixed
	{
		return filter_input( INPUT_GET, $Data );
	}

	/**
	 * @param array $Data
	 * @return array|false|null
	 */

	public static function filterArray( array $Data ) : array | false | null
	{
		return filter_input_array(INPUT_GET, $Data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}
}
