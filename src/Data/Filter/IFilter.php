<?php

namespace Neuron\Data\Filter;

interface IFilter
{
	public static function filterScalar( $Data );
	public static function filterArray( array $Data );
}
