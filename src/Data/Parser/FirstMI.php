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
	 * @param $Text
	 * @param array $UserData
	 * @return array first, middle, last
	 *
	 * @SuppressWarnings(PHPMD)
	 */

	public function parse( $Text, $UserData = array() ) : array
	{
		$Text  = str_replace( '.', '', $Text );
		$Parts = explode( ' ', $Text );

		$Middle = '';

		if( count( $Parts ) > 1 )
		{
			$Parts[ 1 ] = trim( $Parts[ 1 ] );

			if( strlen( $Parts[ 1 ] ) == 1 )
			{
				$Middle = $Parts[ 1 ];
			}
		}

		$First = trim( $Parts[ 0 ] );

		return [ $First, $Middle ];
	}
}
