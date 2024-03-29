<?php

namespace Neuron\Data\Filter;

/**
 * Filters cookies.
 */
class Cookie implements IFilter
{
	public static function filterScalar( $Data )
	{
		return filter_input(INPUT_COOKIE, $Data );
	}

	public static function filterArray( array $Data )
	{
		return filter_input_array(INPUT_COOKIE, $Data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
	}
}
