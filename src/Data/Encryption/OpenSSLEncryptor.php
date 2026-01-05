<?php

namespace Neuron\Data\Encryption;

/**
 * OpenSSL-based encryption implementation
 *
 * Provides secure encryption/decryption using OpenSSL with AES-256-CBC cipher.
 * Includes HMAC authentication to ensure data integrity and authenticity.
 *
 * @package Neuron\Data\Encryption
 */
class OpenSSLEncryptor implements IEncryptor
{
	private const CIPHER = 'aes-256-cbc';
	private const KEY_LENGTH = 32; // 256 bits for AES-256

	/**
	 * Encrypt data using OpenSSL with AES-256-CBC
	 *
	 * The encrypted payload includes:
	 * - Base64 encoded encrypted data
	 * - Base64 encoded initialization vector (IV)
	 * - HMAC for authentication
	 * - Metadata (cipher type)
	 *
	 * @param string $data The plaintext data to encrypt
	 * @param string $key The encryption key
	 * @return string JSON-encoded encrypted payload
	 * @throws \Exception If encryption fails
	 */
	public function encrypt( string $data, string $key ): string
	{
		if( !$this->isValidKey( $key ) )
		{
			throw new \Exception( 'Invalid encryption key. Key must be 32 bytes (256 bits) for AES-256.' );
		}

		// Convert hex key to binary if needed for consistent key format
		if( preg_match( '/^[a-f0-9]{64}$/i', $key ) )
		{
			$key = hex2bin( $key );
		}

		// Generate a random initialization vector
		$ivLength = openssl_cipher_iv_length( self::CIPHER );
		$iv = openssl_random_pseudo_bytes( $ivLength );

		// Encrypt the data
		$encrypted = openssl_encrypt(
			$data,
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$iv
		);

		if( $encrypted === false )
		{
			throw new \Exception( 'Encryption failed: ' . openssl_error_string() );
		}

		// Create the payload
		$payload = [
			'cipher' => self::CIPHER,
			'encrypted' => base64_encode( $encrypted ),
			'iv' => base64_encode( $iv ),
		];

		// Generate HMAC for authentication
		$payload['mac'] = $this->generateMac( $payload, $key );

		// Return as JSON for easy storage
		return json_encode( $payload );
	}

	/**
	 * Decrypt data encrypted with encrypt()
	 *
	 * @param string $encryptedData JSON-encoded encrypted payload
	 * @param string $key The decryption key
	 * @return string The decrypted plaintext data
	 * @throws \Exception If decryption fails or authentication fails
	 */
	public function decrypt( string $encryptedData, string $key ): string
	{
		if( !$this->isValidKey( $key ) )
		{
			throw new \Exception( 'Invalid decryption key. Key must be 32 bytes (256 bits) for AES-256.' );
		}

		// Parse the payload
		$payload = json_decode( $encryptedData, true );

		if( json_last_error() !== JSON_ERROR_NONE )
		{
			throw new \Exception( 'Invalid encrypted data format. Expected JSON payload.' );
		}

		// Verify required fields
		if( !isset( $payload['cipher'], $payload['encrypted'], $payload['iv'], $payload['mac'] ) )
		{
			throw new \Exception( 'Incomplete encrypted payload. Missing required fields.' );
		}

		// Verify cipher type
		if( $payload['cipher'] !== self::CIPHER )
		{
			throw new \Exception( "Cipher mismatch. Expected " . self::CIPHER . ", got {$payload['cipher']}" );
		}

		// Verify MAC for authentication
		if( !$this->verifyMac( $payload, $key ) )
		{
			throw new \Exception( 'MAC verification failed. Data may have been tampered with.' );
		}

		// Convert hex key to binary if needed for consistent key format
		if( preg_match( '/^[a-f0-9]{64}$/i', $key ) )
		{
			$key = hex2bin( $key );
		}

		// Decrypt the data
		$decrypted = openssl_decrypt(
			base64_decode( $payload['encrypted'] ),
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			base64_decode( $payload['iv'] )
		);

		if( $decrypted === false )
		{
			throw new \Exception( 'Decryption failed: ' . openssl_error_string() );
		}

		return $decrypted;
	}

	/**
	 * Generate a cryptographically secure random key
	 *
	 * @return string A 32-byte (256-bit) key encoded as hex
	 * @throws \Exception If key generation fails
	 */
	public function generateKey(): string
	{
		$key = openssl_random_pseudo_bytes( self::KEY_LENGTH, $strong );

		if( !$strong )
		{
			throw new \Exception( 'Failed to generate cryptographically strong key' );
		}

		// Return as hex for easy storage in text files
		return bin2hex( $key );
	}

	/**
	 * Validate that a key meets the requirements
	 *
	 * @param string $key The key to validate (hex encoded or raw binary)
	 * @return bool True if the key is valid
	 */
	public function isValidKey( string $key ): bool
	{
		// Check if it's hex encoded (64 hex chars = 32 bytes)
		if( preg_match( '/^[a-f0-9]{64}$/i', $key ) )
		{
			return true;
		}

		// Check if it's raw binary (32 bytes)
		if( strlen( $key ) === self::KEY_LENGTH )
		{
			return true;
		}

		return false;
	}

	/**
	 * Get the cipher algorithm name
	 *
	 * @return string
	 */
	public function getCipher(): string
	{
		return self::CIPHER;
	}

	/**
	 * Generate HMAC for payload authentication
	 *
	 * @param array $payload The payload to authenticate
	 * @param string $key The key for HMAC
	 * @return string The HMAC hash
	 */
	private function generateMac( array $payload, string $key ): string
	{
		// Prepare data for MAC (exclude the mac field itself)
		$macData = $payload['cipher'] . '.' . $payload['encrypted'] . '.' . $payload['iv'];

		// Convert hex key to binary if needed
		if( preg_match( '/^[a-f0-9]{64}$/i', $key ) )
		{
			$key = hex2bin( $key );
		}

		return hash_hmac( 'sha256', $macData, $key );
	}

	/**
	 * Verify HMAC for payload authentication
	 *
	 * @param array $payload The payload to verify
	 * @param string $key The key for HMAC
	 * @return bool True if MAC is valid
	 */
	private function verifyMac( array $payload, string $key ): bool
	{
		$expectedMac = $this->generateMac( $payload, $key );

		// Use hash_equals to prevent timing attacks
		return hash_equals( $expectedMac, $payload['mac'] );
	}
}