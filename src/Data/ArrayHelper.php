<?php

/**
 * Class ArrayHelper
 */

namespace Neuron\Data;

/**
 * General array help.
 */
class ArrayHelper
{
	/**
	 * @param array $Data
	 * @param $Value
	 * @param $Key
	 * @return bool
	 */

	public static function contains( array $Data, $Value, $Key = null ) : bool
	{
		if( !$Key )
		{
			if( in_array( $Value, $Data ) )
			{
				return true;
			}
		}
		else
		{
			if( !self::hasKey( $Data, $Key ) )
			{
				return false;
			}

			if( $Data[ $Key ] == $Value )
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array $aData
	 * @param $Key
	 * @return bool
	 */

	public static function hasKey( array $aData, $Key ) : bool
	{
		if ( isset( $aData[ $Key ] ) || array_key_exists( $Key, $aData ) )
		{
			return true;
		}

		return false;
	}

	/**
	 * @param array $aData
	 * @param $sKey
	 * @param null $Default
	 * @return mixed|null
	 */

	public static function getElement( array $aData, $sKey, $Default = null ) : mixed
	{
		if( array_key_exists( $sKey, $aData ) )
		{
			return $aData[ $sKey ];
		}

		if( $Default )
		{
			return $Default;
		}

		return null;
	}

	/**
	 * @param array $aData
	 * @param $Item
	 * @return mixed
	 */

	public static function indexOf( array $aData, $Item ): mixed
	{
		return array_search( $Item, $aData );
	}

	/**
	 * @param array $aData
	 * @param $Item
	 * @return bool
	 */

	public static function remove( array &$aData, $Item ) : bool
	{
		$Index = self::indexOf( $aData, $Item );

		if( $Index === false )
		{
			return false;
		}

		unset( $aData[ $Index ] );

		return true;
	}
}
