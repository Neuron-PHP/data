<?php
namespace Neuron\Data\Filter;

/**
 * Filters SERVER data.
 */
class Server implements IFilter
{
	public static function filterScalar( $Data )
	{
		return filter_input( INPUT_SERVER, $Data );
	}

	public static function filterArray( array $Data )
	{
		return filter_input_array(INPUT_SERVER, $Data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}
}
