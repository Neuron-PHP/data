<?php

namespace Neuron\Data\Filter;

/**
 * Interface for sanitizing external PHP variables.
 */
interface IFilter
{
	public static function filterScalar( $Data );
	public static function filterArray( array $Data );
}
