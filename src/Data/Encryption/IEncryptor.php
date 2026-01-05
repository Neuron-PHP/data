<?php

namespace Neuron\Data\Encryption;

/**
 * Interface for encryption/decryption implementations
 *
 * Provides a contract for different encryption methods to implement,
 * allowing the framework to support various encryption algorithms
 * and libraries (OpenSSL, Sodium, etc.)
 *
 * @package Neuron\Data\Encryption
 */
interface IEncryptor
{
	/**
	 * Encrypt data using the provided key
	 *
	 * @param string $data The plaintext data to encrypt
	 * @param string $key The encryption key
	 * @return string The encrypted data (base64 encoded for safe storage)
	 * @throws \Exception If encryption fails
	 */
	public function encrypt( string $data, string $key ): string;

	/**
	 * Decrypt data using the provided key
	 *
	 * @param string $encryptedData The encrypted data (base64 encoded)
	 * @param string $key The decryption key
	 * @return string The decrypted plaintext data
	 * @throws \Exception If decryption fails or data is corrupted
	 */
	public function decrypt( string $encryptedData, string $key ): string;

	/**
	 * Generate a random encryption key
	 *
	 * @return string A cryptographically secure random key
	 * @throws \Exception If key generation fails
	 */
	public function generateKey(): string;

	/**
	 * Validate that a key meets the requirements for this encryptor
	 *
	 * @param string $key The key to validate
	 * @return bool True if the key is valid
	 */
	public function isValidKey( string $key ): bool;

	/**
	 * Get the cipher algorithm name
	 *
	 * @return string The name of the cipher algorithm used
	 */
	public function getCipher(): string;
}