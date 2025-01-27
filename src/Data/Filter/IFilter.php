<?php

namespace Neuron\Data\Filter;

/**
 * Interface for sanitizing external PHP variables.
 */
interface IFilter
{
	public static function filterScalar( $Data ) : mixed;
	public static function filterArray( array $Data ) : array | false | null;
}
