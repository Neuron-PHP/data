<?php

namespace Neuron\Data\Parser;

/**
 * Generic parsing capabilities.
 */
interface IParser
{
	public function parse( string $text, array $userData = array() ) : ?array;
}
