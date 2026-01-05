<?php

namespace Neuron\Data\Settings\Source;

use Neuron\Core\System\IFileSystem;
use Neuron\Core\System\RealFileSystem;
use Neuron\Data\Encryption\IEncryptor;
use Neuron\Data\Encryption\OpenSSLEncryptor;
use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * Encrypted YAML setting source
 *
 * Provides access to settings stored in encrypted YAML files.
 * Used for storing sensitive configuration like database passwords,
 * API keys, and other secrets.
 *
 * @package Neuron\Data\Settings\Source
 */
class Encrypted implements ISettingSource
{
	private array $settings = [];
	private IEncryptor $encryptor;
	private IFileSystem $fs;
	private string $credentialsPath;
	private string $keyPath;

	/**
	 * @param string $credentialsPath Path to encrypted credentials file
	 * @param string $keyPath Path to encryption key file
	 * @param IEncryptor|null $encryptor Encryption implementation
	 * @param IFileSystem|null $fs File system implementation
	 * @throws \Exception If credentials cannot be decrypted
	 */
	public function __construct(
		string $credentialsPath,
		string $keyPath,
		?IEncryptor $encryptor = null,
		?IFileSystem $fs = null
	)
	{
		$this->credentialsPath = $credentialsPath;
		$this->keyPath = $keyPath;
		$this->encryptor = $encryptor ?? new OpenSSLEncryptor();
		$this->fs = $fs ?? new RealFileSystem();

		$this->loadSettings();
	}

	/**
	 * Load and decrypt settings from the encrypted file
	 *
	 * @throws \Exception If file cannot be read or decrypted
	 */
	private function loadSettings(): void
	{
		if( !$this->fs->fileExists( $this->credentialsPath ) )
		{
			// Silently return empty settings if file doesn't exist
			// This allows for optional environment-specific secrets
			$this->settings = [];
			return;
		}

		// Try to get key from file or environment variable
		$key = $this->getKey();

		if( !$key )
		{
			// No key available, return empty settings
			$this->settings = [];
			return;
		}

		try
		{
			$encrypted = $this->fs->readFile( $this->credentialsPath );
			$decrypted = $this->encryptor->decrypt( $encrypted, $key );
			$parsed = YamlParser::parse( $decrypted );

			// Ensure parsed result is an array (YAML can contain scalar values)
			if( !is_array( $parsed ) )
			{
				// If it's a scalar or null, wrap it in an array structure
				$this->settings = $parsed === null ? [] : ['value' => ['data' => $parsed]];
			}
			else
			{
				$this->settings = $parsed;
			}
		}
		catch( \Exception $e )
		{
			throw new \Exception(
				"Failed to load encrypted settings from {$this->credentialsPath}: " . $e->getMessage()
			);
		}
	}

	/**
	 * Get the encryption key from file or environment
	 *
	 * @return string|null The key, or null if not found
	 */
	private function getKey(): ?string
	{
		// Try file first
		if( $this->fs->fileExists( $this->keyPath ) )
		{
			return trim( $this->fs->readFile( $this->keyPath ) );
		}

		// Try environment variable
		$envKey = 'NEURON_' . strtoupper(
			str_replace( ['/', '.', '-'], '_', basename( $this->keyPath, '.key' ) )
		) . '_KEY';

		$envValue = getenv( $envKey );
		if( $envValue !== false )
		{
			return $envValue;
		}

		// Try RAILS_MASTER_KEY for compatibility
		if( basename( $this->keyPath ) === 'master.key' )
		{
			$railsKey = getenv( 'RAILS_MASTER_KEY' );
			if( $railsKey !== false )
			{
				return $railsKey;
			}
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function get( string $sectionName, string $name ): mixed
	{
		if( array_key_exists( $sectionName, $this->settings ) )
		{
			$section = $this->settings[$sectionName];

			if( is_array( $section ) && array_key_exists( $name, $section ) )
			{
				return $section[$name];
			}
		}

		return null;
	}

	/**
	 * @inheritDoc
	 * Note: Setting values in encrypted source requires re-encryption
	 * This is typically done through SecretManager::edit() instead
	 */
	public function set( string $sectionName, string $name, mixed $value ): ISettingSource
	{
		$this->settings[$sectionName][$name] = $value;
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function getSectionNames(): array
	{
		return array_keys( $this->settings );
	}

	/**
	 * @inheritDoc
	 */
	public function getSectionSettingNames( string $section ): array
	{
		if( !isset( $this->settings[$section] ) || !is_array( $this->settings[$section] ) )
		{
			return [];
		}

		return array_keys( $this->settings[$section] );
	}

	/**
	 * @inheritDoc
	 */
	public function getSection( string $sectionName ): ?array
	{
		return $this->settings[$sectionName] ?? null;
	}

	/**
	 * @inheritDoc
	 * Re-encrypts and saves the current settings
	 */
	public function save(): bool
	{
		$key = $this->getKey();

		if( !$key )
		{
			return false;
		}

		try
		{
			$yaml = YamlParser::dump( $this->settings, 4, 2 );
			$encrypted = $this->encryptor->encrypt( $yaml, $key );
			$this->fs->writeFile( $this->credentialsPath, $encrypted );

			return true;
		}
		catch( \Exception $e )
		{
			return false;
		}
	}
}