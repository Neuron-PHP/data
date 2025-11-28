<?php

namespace Neuron\Data\Filters;

/**
 * Interface for sanitizing external PHP variables.
 */
interface IFilter
{
	public static function filterScalar( string $data, mixed $default = null ) : mixed;
	public static function filterArray( array $data ) : array | false | null;
}
