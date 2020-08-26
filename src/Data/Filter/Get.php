<?php

namespace Neuron\Data\Filter;

class Get implements IFilter
{
	public static function filterScalar( $Data )
	{
		return filter_input( INPUT_GET, $Data );
	}

	public static function filterArray( array $Data )
	{
		return filter_input(INPUT_GET, $Data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}
}
