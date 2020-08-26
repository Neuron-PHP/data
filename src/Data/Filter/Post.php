<?php

namespace Neuron\Data\Filter;

class Post implements IFilter
{
	public static function filterScalar( $Data )
	{
		return filter_input(INPUT_POST, $Data );
	}

	public static function filterArray( array $Data )
	{
		return filter_input(INPUT_POST, $Data,FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	}
}
