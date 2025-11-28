<?php
namespace Neuron\Data\Filters;

/**
 * Filters session data.
 */
class Session implements IFilter
{
	/**
	 * @param string $data
	 * @param mixed|null $default
	 * @return mixed
	 */

	public static function filterScalar( string $data, mixed $default = null ) : mixed
	{
		if( !isset( $_SESSION[ $data ] ) )
		{
			return $default;
		}

		return filter_var( $_SESSION[ $data ] ) ;
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
