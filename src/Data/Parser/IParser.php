<?php

namespace Neuron\Data\Parser;

/**
 * Generic parsing capabilities.
 */
interface IParser
{
	public function parse( $Text, $UserData = array() ) : array;
}
