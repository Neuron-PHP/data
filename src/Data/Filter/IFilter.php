<?php

namespace Neuron\Data\Filter;

/**
 * Interface for sanitizing external PHP variables.
 */
interface IFilter
{
	public static function filterScalar( $data ) : mixed;
	public static function filterArray( array $data ) : array | false | null;
}
