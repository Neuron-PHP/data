<?php

namespace Neuron\Data\Parser;

/**
 * Generic parsing capabilities.
 */
interface IParser
{
	public function parse( string $Text, array $UserData = array() ) : ?array;
}
