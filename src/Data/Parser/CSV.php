<?php

namespace Neuron\Data\Parser;

/**
 * Parses a line of CSV data to an associative array.
 */
class CSV implements IParser
{
	public array $_Results;

	/**
	 * @param $Text
	 * @param array $Columns
	 * @return ?array
	 */

	public function parse( $Text, $Columns = array() ) : ?array
	{
		$Results = array();

		$Data = str_getcsv( $Text );

		$idx = 0;

		foreach( $Columns as $Column )
		{
			if( !isset( $Data[ $idx ] ) )
			{
				$this->_Results = $Results;
				return null;
			}

			$Results[ $Column ] = $Data[ $idx ];
			++$idx;
		}

		return $Results;
	}
}

