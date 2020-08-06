<?php

namespace Neuron\Data\Parser;

class CSV implements IParser
{
	public array $_Results;

	/**
	 * @param $Text
	 * @param array $Columns
	 * @return array|bool
	 */

	public function parse( $Text, $Columns = array() )
	{
		$Results = array();

		$Data = str_getcsv( $Text );

		$idx = 0;

		foreach( $Columns as $Column )
		{
			$Results[ $Column ] = $Data[ $idx ];
			++$idx;
		}

		if( count( $Columns ) != count( $Data ) )
		{
			$this->_Results = $Results;
			return false;
		}

		return $Results;
	}
}

