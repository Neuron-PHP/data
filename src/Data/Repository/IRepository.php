<?php

namespace Neuron\Data\Repository;

/**
 * Repository interface for storing and retrieving serializable data.
 *
 * The IRepository interface provides a consistent API for persisting data across
 * different storage backends (Redis, Memory, File, etc.). It supports basic CRUD
 * operations with optional time-to-live (TTL) for automatic expiration.
 *
 *
 * @package Neuron\Data\Repository
 *
 * @example
 * ```php
 * // Store a DTO with 1 hour expiration
 * $repo = new Redis();
 * $repo->save('user:123', $userDto, 3600);
 *
 * // Retrieve the DTO
 * $dto = $repo->find('user:123');
 *
 * // Check existence
 * if ($repo->exists('user:123')) {
 *     $repo->delete('user:123');
 * }
 * ```
 */
interface IRepository
{
	/**
	 * Save a value to the repository.
	 *
	 * @param string $key The unique key to store the value under
	 * @param mixed $value The value to store (must be serializable)
	 * @param int $ttl Time-to-live in seconds (0 = no expiration)
	 * @return bool True on success, false on failure
	 */
	public function save( string $key, mixed $value, int $ttl = 0 ): bool;

	/**
	 * Find a value by key.
	 *
	 * @param string $key The key to look up
	 * @return mixed The stored value, or null if not found or expired
	 */
	public function find( string $key ): mixed;

	/**
	 * Delete a value by key.
	 *
	 * @param string $key The key to delete
	 * @return bool True if deleted, false if key didn't exist
	 */
	public function delete( string $key ): bool;

	/**
	 * Check if a key exists in the repository.
	 *
	 * @param string $key The key to check
	 * @return bool True if exists and not expired, false otherwise
	 */
	public function exists( string $key ): bool;
}
