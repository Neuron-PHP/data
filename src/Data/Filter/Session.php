<?php
namespace Neuron\Data\Filter;

/**
 * Filters session data.
 */
class Session implements IFilter
{
	/**
	 * @param string $Data
	 * @return mixed
	 */

	public static function filterScalar( $Data ) : mixed
	{
		if( !isset( $_SESSION[ $Data ] ) )
		{
			return null;
		}

		return filter_var( $_SESSION[ $Data ]) ;
	}

	/**
	 * @param array $Data
	 * @return array|false|null
	 */

	public static function filterArray( array $Data ) : array | false | null
	{
		return filter_var_array( $Data );
	}
}
