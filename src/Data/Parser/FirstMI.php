<?php

namespace Neuron\Data\Parser;

/**
 * Parses first name middle initial.
 */
class FirstMI implements IParser
{
	/**
	 * Parses First M
	 *
	 * @param $text
	 * @param array $userData
	 * @return array first, middle, last
	 *
	 * @SuppressWarnings(PHPMD)
	 */

	public function parse( $text, $userData = array() ) : array
	{
		$text  = str_replace( '.', '', $text );
		$parts = explode( ' ', $text );

		$middle = '';

		if( count( $parts ) > 1 )
		{
			$parts[ 1 ] = trim( $parts[ 1 ] );

			if( strlen( $parts[ 1 ] ) == 1 )
			{
				$middle = $parts[ 1 ];
			}
		}

		$first = trim( $parts[ 0 ] );

		return [ $first, $middle ];
	}
}
