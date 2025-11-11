<?php
namespace Neuron\Data\Filter;

/**
 * Filters session data.
 */
class Session implements IFilter
{
	/**
	 * @param string $data
	 * @return mixed
	 */

	public static function filterScalar( $data ) : mixed
	{
		if( !isset( $_SESSION[ $data ] ) )
		{
			return null;
		}

		return filter_var( $_SESSION[ $data ]) ;
	}

	/**
	 * @param array $data
	 * @return array|false|null
	 */

	public static function filterArray( array $data ) : array | false | null
	{
		return filter_var_array( $data );
	}
}
