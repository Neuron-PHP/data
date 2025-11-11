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
	 * @param array $data
	 * @param $value
	 * @param $key
	 * @return bool
	 */

	public static function contains( array $data, $value, $key = null ) : bool
	{
		if( !$key )
		{
			if( in_array( $value, $data ) )
			{
				return true;
			}
		}
		else
		{
			if( !self::hasKey( $data, $key ) )
			{
				return false;
			}

			if( $data[ $key ] == $value )
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array $data
	 * @param $key
	 * @return bool
	 */

	public static function hasKey( array $data, $key ) : bool
	{
		if ( isset( $data[ $key ] ) || array_key_exists( $key, $data ) )
		{
			return true;
		}

		return false;
	}

	/**
	 * @param array $data
	 * @param $key
	 * @param null $default
	 * @return mixed|null
	 */

	public static function getElement( array $data, $key, $default = null ) : mixed
	{
		if( array_key_exists( $key, $data ) )
		{
			return $data[ $key ];
		}

		if( $default )
		{
			return $default;
		}

		return null;
	}

	/**
	 * @param array $data
	 * @param $item
	 * @return mixed
	 */

	public static function indexOf( array $data, $item ): mixed
	{
		return array_search( $item, $data );
	}

	/**
	 * @param array $data
	 * @param $item
	 * @return bool
	 */

	public static function remove( array &$data, $item ) : bool
	{
		$index = self::indexOf( $data, $item );

		if( $index === false )
		{
			return false;
		}

		unset( $data[ $index ] );

		return true;
	}
}
