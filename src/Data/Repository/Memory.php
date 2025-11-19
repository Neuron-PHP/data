<?php

namespace Neuron\Data\Repository;

/**
 * In-memory repository implementation for testing and development.
 *
 * The Memory repository stores data in a PHP array with automatic expiration
 * support.
 *
 * Note: Data is lost when the PHP process ends. For persistent storage,
 * use Redis or File repository implementations.
 *
 * @package Neuron\Data\Repository
 *
 * @example
 * ```php
 * $repo = new Memory();
 * $repo->save('temp:data', ['foo' => 'bar'], 60);
 * $data = $repo->find('temp:data');
 * ```
 */
class Memory implements IRepository
{
	private array $storage = [];
	private array $expiration = [];

	/**
	 * Save a value to memory.
	 *
	 * @param string $key The unique key to store the value under
	 * @param mixed $value The value to store
	 * @param int $ttl Time-to-live in seconds (0 = no expiration)
	 * @return bool Always returns true
	 */
	public function save( string $key, mixed $value, int $ttl = 0 ): bool
	{
		$this->storage[ $key ] = $value;

		if( $ttl > 0 )
		{
			$this->expiration[ $key ] = time() + $ttl;
		}
		else
		{
			unset( $this->expiration[ $key ] );
		}

		return true;
	}

	/**
	 * Find a value by key.
	 *
	 * @param string $key The key to look up
	 * @return mixed The stored value, or null if not found or expired
	 */
	public function find( string $key ): mixed
	{
		if( !array_key_exists( $key, $this->storage ) )
		{
			return null;
		}

		// Check if expired
		if( $this->isExpired( $key ) )
		{
			$this->delete( $key );
			return null;
		}

		return $this->storage[ $key ];
	}

	/**
	 * Delete a value by key.
	 *
	 * @param string $key The key to delete
	 * @return bool True if deleted, false if key didn't exist
	 */
	public function delete( string $key ): bool
	{
		if( !array_key_exists( $key, $this->storage ) )
		{
			return false;
		}

		unset( $this->storage[ $key ] );
		unset( $this->expiration[ $key ] );

		return true;
	}

	/**
	 * Check if a key exists in the repository.
	 *
	 * @param string $key The key to check
	 * @return bool True if exists and not expired, false otherwise
	 */
	public function exists( string $key ): bool
	{
		if( !array_key_exists( $key, $this->storage ) )
		{
			return false;
		}

		if( $this->isExpired( $key ) )
		{
			$this->delete( $key );
			return false;
		}

		return true;
	}

	/**
	 * Check if a key has expired.
	 *
	 * @param string $key The key to check
	 * @return bool True if expired, false otherwise
	 */
	private function isExpired( string $key ): bool
	{
		if( !array_key_exists( $key, $this->expiration ) )
		{
			return false;
		}

		return time() >= $this->expiration[ $key ];
	}

	/**
	 * Clear all data from the repository.
	 *
	 * @return void
	 */
	public function clear(): void
	{
		$this->storage = [];
		$this->expiration = [];
	}

	/**
	 * Get all keys in the repository (including expired).
	 *
	 * @return array
	 */
	public function keys(): array
	{
		return array_keys( $this->storage );
	}
}
