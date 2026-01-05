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

		// Create temporary file with cryptographically secure token
		$tempFile = sys_get_temp_dir() . '/neuron_credentials_' . $this->generateSecureToken() . '.yml';
		$this->fs->writeFile( $tempFile, $content );

		// Set restrictive permissions to protect decrypted secrets (owner read/write only)
		chmod( $tempFile, 0600 );

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
				$this->fs->unlink( $tempFile );
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

		// Create temporary files for atomic operation with secure tokens
		$tempKeyFile = sys_get_temp_dir() . '/neuron_key_' . $this->generateSecureToken() . '.tmp';
		$tempCredentialsFile = sys_get_temp_dir() . '/neuron_creds_' . $this->generateSecureToken() . '.tmp';

		// Create backup files for rollback if needed with secure tokens
		$backupKeyFile = null;
		$backupCredentialsFile = $credentialsPath . '.backup_' . $this->generateSecureToken();

		// Track whether credentials have been updated with new key
		$credentialsUpdatedWithNewKey = false;

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
			chmod( $tempCredentialsFile, 0600 );

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
				$backupKeyFile = $oldKeyPath . '.backup_' . $this->generateSecureToken();
				if( !copy( $oldKeyPath, $backupKeyFile ) )
				{
					// Clean up credentials backup since we can't proceed
					$this->fs->unlink( $backupCredentialsFile );
					throw new \Exception( 'Failed to create backup of key file' );
				}
			}

			// Step 7: Atomically move the new files to their final locations
			// Move credentials first (we still have the old key if this fails)
			if( !rename( $tempCredentialsFile, $credentialsPath ) )
			{
				throw new \Exception( 'Failed to update credentials file' );
			}

			// Mark that credentials are now encrypted with the new key
			$credentialsUpdatedWithNewKey = true;

			// Move the new key to its final location
			if( !rename( $tempKeyFile, $newKeyPath ) )
			{
				// Rollback credentials since key update failed
				// CRITICAL: Check if rollback succeeds before losing the new key!
				if( !rename( $backupCredentialsFile, $credentialsPath ) )
				{
					// Rollback failed! The credentials are still encrypted with the new key.
					// We MUST preserve the new key to prevent data loss.
					// Try to save the new key to an emergency location
					$emergencyKeyPath = $newKeyPath . '.emergency_' . $this->generateSecureToken();
					if( rename( $tempKeyFile, $emergencyKeyPath ) )
					{
						throw new \Exception(
							'CRITICAL: Key rotation partially failed. ' .
							'Credentials remain encrypted with new key saved at: ' . $emergencyKeyPath . ' ' .
							'Manual intervention required to complete rotation.'
						);
					}
					else
					{
						// Last resort: try to copy the temp key before it might be deleted
						@copy( $tempKeyFile, $emergencyKeyPath );
						throw new \Exception(
							'CRITICAL: Key rotation failed and rollback failed. ' .
							'Attempting to preserve new key at: ' . $emergencyKeyPath . ' ' .
							'Data may be at risk. Manual intervention urgently required.'
						);
					}
				}
				// Rollback succeeded, credentials are back to using old key
				throw new \Exception( 'Failed to update key file, but credentials successfully rolled back' );
			}

			// Step 8: Clean up backups on success
			if( $this->fs->fileExists( $backupCredentialsFile ) )
			{
				$this->fs->unlink( $backupCredentialsFile );
			}

			if( $backupKeyFile && $this->fs->fileExists( $backupKeyFile ) )
			{
				$this->fs->unlink( $backupKeyFile );
			}

			return true;
		}
		catch( \Exception $e )
		{
			// Clean up temporary files
			if( $this->fs->fileExists( $tempKeyFile ) )
			{
				$this->fs->unlink( $tempKeyFile );
			}

			if( $this->fs->fileExists( $tempCredentialsFile ) )
			{
				$this->fs->unlink( $tempCredentialsFile );
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
					$this->fs->unlink( $backupCredentialsFile );
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
					$this->fs->unlink( $backupKeyFile );
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

			$envValue = getenv( $envKey );
			if( $envValue !== false )
			{
				return $envValue;
			}

			// Generate new key
			return $this->generateKey( $keyPath );
		}

		return trim( $this->fs->readFile( $keyPath ) );
	}

	/**
	 * Generate a cryptographically secure random token for temporary files
	 *
	 * This method generates a secure random token suitable for use in
	 * temporary file names. The token is URL-safe and filesystem-safe.
	 *
	 * @param int $length Number of random bytes (will produce 2x hex characters)
	 * @return string A secure random hex string
	 * @throws \Exception If secure random generation fails
	 */
	private function generateSecureToken( int $length = 16 ): string
	{
		try
		{
			// Generate cryptographically secure random bytes
			$bytes = random_bytes( $length );

			// Convert to hex for filesystem safety
			// This produces a string of 2 * $length characters
			return bin2hex( $bytes );
		}
		catch( \Exception $e )
		{
			throw new \Exception( 'Failed to generate secure random token: ' . $e->getMessage() );
		}
	}
}