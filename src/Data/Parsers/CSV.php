<?php

namespace Neuron\Data\Parsers;

/**
 * Parses a line of CSV data to an associative array.
 */
class CSV implements IParser
{
	public array $results;

	/**
	 * @param $text
	 * @param array $columns
	 * @return ?array
	 */

	public function parse( $text, $columns = array() ) : ?array
	{
		$results = array();
		$data = str_getcsv($text, ",", "\"", "\\");

		$idx = 0;

		foreach( $columns as $column )
		{
			if( !isset( $data[ $idx ] ) )
			{
				$this->results = $results;
				return null;
			}

			$results[ $column ] = $data[ $idx ];
			++$idx;
		}

		return $results;
	}
}

