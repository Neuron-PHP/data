<?php
namespace Neuron\Data\Filter;

/**
 * Filters session data.
 */
class Session implements IFilter
{
	public static function filterScalar( $Data )
	{
		return filter_var( $_SESSION[ $Data ] );
	}

	public static function filterArray( array $Data )
	{
		return filter_var_array( $Data );
	}
}
