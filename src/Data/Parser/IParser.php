<?php

namespace Neuron\Data\Parser;

/**
 * Parser interface.
 */
interface IParser
{
	public function parse($Text, $UserData = array() ) : array;
}
