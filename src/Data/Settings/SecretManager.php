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
	 * Re-encrypts credentials with a new key in an atomic operation to prevent
	 * data loss if any step fails. The old key and credentials are preserved
	 * until the entire operation succeeds.
	 *
	 * @param string $credentialsPath Path to encrypted credentials file
	 * @param string $oldKeyPath Path to current encryption key
	 * @param string $newKeyPath Path where new key will be saved
	 * @return bool True if successful
	 * @throws \Exception If rotation fails
	 */
	public function rotateKey( string $credentialsPath, string $oldKeyPath, string $newKeyPath ): bool
	{
		// Validate inputs
		if( !$this->fs->fileExists( $credentialsPath ) )
		{
			throw new \Exception( "Credentials file not found: $credentialsPath" );
		}

		if( !$this->fs->fileExists( $oldKeyPath ) )
		{
			throw new \Exception( "Old key file not found: $oldKeyPath" );
		}

		// Read the old key first (don't modify anything yet)
		$oldKey = trim( $this->fs->readFile( $oldKeyPath ) );

		// Check if we're rotating the key in-place
		$inPlaceRotation = realpath( $oldKeyPath ) === realpath( $newKeyPath );

		// Create temporary files for atomic operation
		$tempKeyFile = sys_get_temp_dir() . '/neuron_key_' . uniqid() . '.tmp';
		$tempCredentialsFile = sys_get_temp_dir() . '/neuron_creds_' . uniqid() . '.tmp';

		// Create backup files for rollback if needed
		$backupKeyFile = null;
		$backupCredentialsFile = $credentialsPath . '.backup_' . uniqid();

		try
		{
			// Step 1: Decrypt with old key (validates we can read the data)
			$encrypted = $this->fs->readFile( $credentialsPath );
			$content = $this->encryptor->decrypt( $encrypted, $oldKey );

			// Step 2: Generate new key to temporary location (not overwriting anything yet)
			$newKey = $this->encryptor->generateKey();
			$this->fs->writeFile( $tempKeyFile, $newKey );
			chmod( $tempKeyFile, 0600 );

			// Step 3: Re-encrypt with new key to temporary file
			$newEncrypted = $this->encryptor->encrypt( $content, $newKey );
			$this->fs->writeFile( $tempCredentialsFile, $newEncrypted );

			// Step 4: Verify the new encryption worked (decrypt and compare)
			$verifyContent = $this->encryptor->decrypt( $newEncrypted, $newKey );
			if( $verifyContent !== $content )
			{
				throw new \Exception( 'Verification failed: Re-encrypted content does not match original' );
			}

			// Step 5: Create backup of current credentials
			if( !copy( $credentialsPath, $backupCredentialsFile ) )
			{
				throw new \Exception( 'Failed to create backup of credentials file' );
			}

			// Step 6: If rotating in-place, backup the old key
			if( $inPlaceRotation )
			{
				$backupKeyFile = $oldKeyPath . '.backup_' . uniqid();
				if( !copy( $oldKeyPath, $backupKeyFile ) )
				{
					// Clean up credentials backup since we can't proceed
					$this->fs->deleteFile( $backupCredentialsFile );
					throw new \Exception( 'Failed to create backup of key file' );
				}
			}

			// Step 7: Atomically move the new files to their final locations
			// Move credentials first (we still have the old key if this fails)
			if( !rename( $tempCredentialsFile, $credentialsPath ) )
			{
				throw new \Exception( 'Failed to update credentials file' );
			}

			// Move the new key to its final location
			if( !rename( $tempKeyFile, $newKeyPath ) )
			{
				// Rollback credentials since key update failed
				rename( $backupCredentialsFile, $credentialsPath );
				throw new \Exception( 'Failed to update key file' );
			}

			// Step 8: Clean up backups on success
			if( $this->fs->fileExists( $backupCredentialsFile ) )
			{
				$this->fs->deleteFile( $backupCredentialsFile );
			}

			if( $backupKeyFile && $this->fs->fileExists( $backupKeyFile ) )
			{
				$this->fs->deleteFile( $backupKeyFile );
			}

			return true;
		}
		catch( \Exception $e )
		{
			// Clean up temporary files
			if( $this->fs->fileExists( $tempKeyFile ) )
			{
				$this->fs->deleteFile( $tempKeyFile );
			}

			if( $this->fs->fileExists( $tempCredentialsFile ) )
			{
				$this->fs->deleteFile( $tempCredentialsFile );
			}

			// Attempt to restore from backups if they exist
			if( $backupCredentialsFile && $this->fs->fileExists( $backupCredentialsFile ) )
			{
				// Only restore if main file was modified
				if( !$this->fs->fileExists( $credentialsPath ) ||
				    $this->fs->readFile( $credentialsPath ) !== $encrypted )
				{
					rename( $backupCredentialsFile, $credentialsPath );
				}
				else
				{
					$this->fs->deleteFile( $backupCredentialsFile );
				}
			}

			if( $backupKeyFile && $this->fs->fileExists( $backupKeyFile ) )
			{
				// Restore the old key if we were rotating in-place
				if( $inPlaceRotation )
				{
					rename( $backupKeyFile, $oldKeyPath );
				}
				else
				{
					$this->fs->deleteFile( $backupKeyFile );
				}
			}

			throw new \Exception( 'Key rotation failed: ' . $e->getMessage() .
			                     '. Original data has been preserved.', 0, $e );
		}
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