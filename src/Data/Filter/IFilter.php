<?php

namespace Neuron\Data\Filter;

/**
 * Filter interface
 */
interface IFilter
{
	public static function filterScalar( $Data );
	public static function filterArray( array $Data );
}
