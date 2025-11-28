<?php

namespace Neuron\Data\Parsers;

/**
 * Parses last, first m
 */

class LastFirstMI implements IParser
{
	/**
	 * Parses Last, First M
	 *
	 * @param $text
	 * @param array $userData
	 * @return array first, middle, last
	 *
	 * @SuppressWarnings(PHPMD)
	 */

	public function parse( $text, $userData = array() ) : array
	{
		$name = explode( ',', $text );

		$first  = trim( $name[ 1 ] );
		$middle = '';

		$firstMiddle = explode( ' ', $first );

		if( count( $firstMiddle ) > 1 )
		{
			$firstMiddle[ 1 ] = trim( $firstMiddle[ 1 ] );

			if( strlen( $firstMiddle[ 1 ] ) == 1 )
			{
				$first  = $firstMiddle[ 0 ];
				$middle = $firstMiddle[ 1 ];
			}
		}

		$last = trim( $name[ 0 ] );

		return [ $first, $middle, $last ];
	}
}
