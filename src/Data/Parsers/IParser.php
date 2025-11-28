<?php

namespace Neuron\Data\Parsers;

/**
 * Generic parsing capabilities.
 */
interface IParser
{
	public function parse( string $text, array $userData = array() ) : ?array;
}
