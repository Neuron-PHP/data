<?php

namespace Neuron\Data\Settings;

use Neuron\Core\System\IFileSystem;
use Neuron\Core\System\RealFileSystem;
use Neuron\Data\Encryption\IEncryptor;
use Neuron\Data\Encryption\OpenSSLEncryptor;
use Symfony\Component\Yaml\Yaml;

/**
 * Manages encrypted credentials and secrets
 *
 * Provides functionality to create, edit, and manage encrypted credential files
 * similar to Rails encrypted credentials. Supports environment-specific secrets
 * and secure key management.
 *
 * @package Neuron\Data\Settings
 */
class SecretManager
{
	private IEncryptor $encryptor;
	private IFileSystem $fs;

	/**
	 * @param IEncryptor|null $encryptor Encryption implementation (defaults to OpenSSLEncryptor)
	 * @param IFileSystem|null $fs File system implementation (defaults to RealFileSystem)
	 */
	public function __construct( ?IEncryptor $encryptor = null, ?IFileSystem $fs = null )
	{
		$this->encryptor = $encryptor ?? new OpenSSLEncryptor();
		$this->fs = $fs ?? new RealFileSystem();
	}

	/**
	 * Edit encrypted credentials file
	 *
	 * Opens the decrypted credentials in an editor, then re-encrypts on save.
	 * Similar to Rails' credentials:edit command.
	 *
	 * @param string $credentialsPath Path to encrypted credentials file
	 * @param string $keyPath Path to encryption key file
	 * @param string $editor Editor command to use (default: vi)
	 * @return bool True if edit was successful
	 * @throws \Exception If editing fails
	 */
	public function edit( string $credentialsPath, string $keyPath, string $editor = 'vi' ): bool
	{
		// Ensure key exists or create it
		$key = $this->ensureKey( $keyPath );

		// Decrypt existing credentials or start with empty
		$content = '';
		if( $this->fs->fileExists( $credentialsPath ) )
		{
			$encrypted = $this->fs->readFile( $credentialsPath );
			$content = $this->encryptor->decrypt( $encrypted, $key );
		}

		// Create temporary file
		$tempFile = sys_get_temp_dir() . '/neuron_credentials_' . uniqid() . '.yml';
		$this->fs->writeFile( $tempFile, $content );

		try
		{
			// Open in editor
			$command = escapeshellcmd( $editor ) . ' ' . escapeshellarg( $tempFile );
			$returnCode = 0;
			passthru( $command, $returnCode );

			if( $returnCode !== 0 )
			{
				throw new \Exception( "Editor exited with code $returnCode" );
			}

			// Read edited content
			$editedContent = $this->fs->readFile( $tempFile );

			// Validate YAML syntax
			try
			{
				Yaml::parse( $editedContent );
			}
			catch( \Exception $e )
			{
				throw new \Exception( "Invalid YAML syntax: " . $e->getMessage() );
			}

			// Encrypt and save
			$encrypted = $this->encryptor->encrypt( $editedContent, $key );
			$this->fs->writeFile( $credentialsPath, $encrypted );

			return true;
		}
		finally
		{
			// Always clean up temp file
			if( $this->fs->fileExists( $tempFile ) )
			{
				$this->fs->deleteFile( $tempFile );
			}
		}
	}

	/**
	 * Show decrypted credentials
	 *
	 * @param string $credentialsPath Path to encrypted credentials file
	 * @param string $keyPath Path to encryption key file
	 * @return string The decrypted YAML content
	 * @throws \Exception If decryption fails
	 */
	public function show( string $credentialsPath, string $keyPath ): string
	{
		if( !$this->fs->fileExists( $credentialsPath ) )
		{
			throw new \Exception( "Credentials file not found: $credentialsPath" );
		}

		if( !$this->fs->fileExists( $keyPath ) )
		{
			throw new \Exception( "Key file not found: $keyPath" );
		}

		$key = trim( $this->fs->readFile( $keyPath ) );
		$encrypted = $this->fs->readFile( $credentialsPath );

		return $this->encryptor->decrypt( $encrypted, $key );
	}

	/**
	 * Encrypt plaintext credentials
	 *
	 * @param string $plaintextPath Path to plaintext YAML file
	 * @param string $credentialsPath Path where encrypted file will be saved
	 * @param string $keyPath Path to encryption key file
	 * @return bool True if successful
	 * @throws \Exception If encryption fails
	 */
	public function encrypt( string $plaintextPath, string $credentialsPath, string $keyPath ): bool
	{
		if( !$this->fs->fileExists( $plaintextPath ) )
		{
			throw new \Exception( "Plaintext file not found: $plaintextPath" );
		}

		$content = $this->fs->readFile( $plaintextPath );

		// Validate YAML syntax
		try
		{
			Yaml::parse( $content );
		}
		catch( \Exception $e )
		{
			throw new \Exception( "Invalid YAML syntax in $plaintextPath: " . $e->getMessage() );
		}

		$key = $this->ensureKey( $keyPath );
		$encrypted = $this->encryptor->encrypt( $content, $key );

		$this->fs->writeFile( $credentialsPath, $encrypted );

		return true;
	}

	/**
	 * Generate a new encryption key
	 *
	 * @param string $keyPath Path where key will be saved
	 * @param bool $force Overwrite existing key if true
	 * @return string The generated key
	 * @throws \Exception If key generation fails or file exists and force is false
	 */
	public function generateKey( string $keyPath, bool $force = false ): string
	{
		if( $this->fs->fileExists( $keyPath ) && !$force )
		{
			throw new \Exception( "Key file already exists: $keyPath. Use --force to overwrite." );
		}

		$key = $this->encryptor->generateKey();
		$this->fs->writeFile( $keyPath, $key );

		// Ensure restrictive permissions (owner read/write only)
		chmod( $keyPath, 0600 );

		return $key;
	}

	/**
	 * Validate that credentials can be decrypted
	 *
	 * @param string $credentialsPath Path to encrypted credentials file
	 * @param string $keyPath Path to encryption key file
	 * @return bool True if valid, false otherwise
	 */
	public function validate( string $credentialsPath, string $keyPath ): bool
	{
		try
		{
			$this->show( $credentialsPath, $keyPath );
			return true;
		}
		catch( \Exception $e )
		{
			return false;
		}
	}

	/**
	 * Rotate encryption keys
	 *
	 * Re-encrypts credentials with a new key
	 *
	 * @param string $credentialsPath Path to encrypted credentials file
	 * @param string $oldKeyPath Path to current encryption key
	 * @param string $newKeyPath Path where new key will be saved
	 * @return bool True if successful
	 * @throws \Exception If rotation fails
	 */
	public function rotateKey( string $credentialsPath, string $oldKeyPath, string $newKeyPath ): bool
	{
		// Decrypt with old key
		$content = $this->show( $credentialsPath, $oldKeyPath );

		// Generate new key
		$newKey = $this->generateKey( $newKeyPath, true );

		// Re-encrypt with new key
		$encrypted = $this->encryptor->encrypt( $content, $newKey );
		$this->fs->writeFile( $credentialsPath, $encrypted );

		return true;
	}

	/**
	 * Ensure key exists, create if needed
	 *
	 * @param string $keyPath Path to key file
	 * @return string The key
	 * @throws \Exception If key cannot be created or read
	 */
	private function ensureKey( string $keyPath ): string
	{
		if( !$this->fs->fileExists( $keyPath ) )
		{
			// Check environment variable as fallback
			$envKey = 'NEURON_' . strtoupper(
				str_replace( ['/', '.', '-'], '_', basename( $keyPath, '.key' ) )
			) . '_KEY';

			if( isset( $_ENV[$envKey] ) )
			{
				return $_ENV[$envKey];
			}

			// Generate new key
			return $this->generateKey( $keyPath );
		}

		return trim( $this->fs->readFile( $keyPath ) );
	}
}