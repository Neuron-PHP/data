<?php

namespace Neuron\Data;

/**
 * Comprehensive array manipulation utility class for the Neuron framework.
 * 
 * @deprecated 3.0.0 Use Neuron\Core\NArray instead for object-oriented array operations
 * @see \Neuron\Core\NArray
 * 
 * This static helper class provides essential array operations and utilities
 * for common data manipulation tasks throughout the framework. It offers
 * safe array access methods, element searching, validation, and modification
 * operations with proper error handling and type safety.
 * 
 * **DEPRECATED**: This class is deprecated in favor of the new NArray class
 * in the Core component, which provides the same functionality with an 
 * object-oriented approach, method chaining, and additional features.
 * 
 * Key features:
 * - Safe array element access with default values
 * - Key existence checking and validation
 * - Element searching and indexing operations
 * - Array modification methods (add, remove elements)
 * - Value containment checking with optional key filtering
 * - Consistent null-safe operations throughout
 * 
 * All methods are static for convenient usage without instantiation,
 * making them ideal for utility functions across the application.
 * 
 * @package Neuron\Data
 * 
 * @example
 * ```php
 * // OLD (Deprecated): Static ArrayHelper usage
 * $config = ['host' => 'localhost', 'port' => 3306];
 * $host = ArrayHelper::getElement($config, 'host', 'default');     // 'localhost'
 * $timeout = ArrayHelper::getElement($config, 'timeout', 30);     // 30 (default)
 * 
 * // NEW: Use NArray instead
 * use Neuron\Core\NArray;
 * $config = new NArray(['host' => 'localhost', 'port' => 3306]);
 * $host = $config->getElement('host', 'default');                  // 'localhost'
 * $timeout = $config->getElement('timeout', 30);                   // 30 (default)
 * 
 * // OLD: Key and value checking
 * if (ArrayHelper::hasKey($config, 'database')) {
 *     // Handle database configuration
 * }
 * 
 * // NEW: Object-oriented approach with method chaining
 * if ($config->hasKey('database')) {
 *     // Handle database configuration
 * }
 * 
 * // OLD: Array manipulation
 * $users = ['alice', 'bob', 'charlie'];
 * $index = ArrayHelper::indexOf($users, 'bob');        // 1
 * ArrayHelper::remove($users, 'bob');                  // ['alice', 'charlie']
 * 
 * // NEW: Fluent interface with method chaining
 * $users = new NArray(['alice', 'bob', 'charlie']);
 * $index = $users->indexOf('bob');                     // 1
 * $filtered = $users->remove('bob');                   // NArray(['alice', 'charlie'])
 * ```
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
