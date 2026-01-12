<?php

namespace Neuron\Data\Settings;

use Neuron\Data\Settings\Source\Yaml;
use Neuron\Data\Settings\Source\Env;
use Neuron\Data\Settings\Source\Encrypted;

/**
 * Factory for creating configured SettingManager instances
 *
 * Simplifies the creation of SettingManager with the standard
 * configuration hierarchy:
 * 1. Base application configuration
 * 2. Environment-specific configuration
 * 3. Base encrypted secrets
 * 4. Environment-specific encrypted secrets
 * 5. Environment variables (highest priority)
 *
 * @package Neuron\Data\Settings
 */
class SettingManagerFactory
{
	/**
	 * Create a fully configured SettingManager
	 *
	 * @param string|null $environment Force a specific environment (null = auto-detect)
	 * @param string $configPath Base path for configuration files
	 * @return SettingManager
	 */
	public static function create( ?string $environment = null, string $configPath = 'config' ): SettingManager
	{
		$env = $environment ?? EnvironmentDetector::detect();
		$manager = new SettingManager();

		// Layer 1: Base application configuration (lowest priority)
		$appConfigPath = $configPath . '/neuron.yaml';
		if( file_exists( $appConfigPath ) )
		{
			$manager->setFallback( new Yaml( $appConfigPath ) );
		}

		// Layer 2: Environment-specific configuration
		$envConfigPath = $configPath . '/environments/' . $env . '.yaml';
		if( file_exists( $envConfigPath ) )
		{
			$manager->setSource( new Yaml( $envConfigPath ) );
		}

		// Layer 3: Base encrypted secrets
		$secretsPath = $configPath . '/secrets.yml.enc';
		$masterKeyPath = $configPath . '/master.key';
		if( file_exists( $secretsPath ) )
		{
			try
			{
				$encrypted = new Encrypted( $secretsPath, $masterKeyPath );
				$manager->addSource( $encrypted, 'secrets' );
			}
			catch( \Exception $e )
			{
				// Silently skip if secrets can't be loaded (key might be in env var)
			}
		}

		// Layer 4: Environment-specific encrypted secrets
		$envSecretsPath = $configPath . '/environments/' . $env . '.secrets.yml.enc';
		$envKeyPath = $configPath . '/environments/' . $env . '.key';
		if( file_exists( $envSecretsPath ) )
		{
			try
			{
				$encrypted = new Encrypted( $envSecretsPath, $envKeyPath );
				$manager->addSource( $encrypted, 'secrets:' . $env );
			}
			catch( \Exception $e )
			{
				// Silently skip if environment secrets can't be loaded
			}
		}

		// Layer 5: Environment variables (highest priority)
		$manager->addSource( new Env( \Neuron\Data\Env::getInstance() ), 'environment' );

		return $manager;
	}

	/**
	 * Create a minimal SettingManager with only the specified sources
	 *
	 * @param array $sources Array of source configurations
	 * @return SettingManager
	 */
	public static function createCustom( array $sources ): SettingManager
	{
		$manager = new SettingManager();

		foreach( $sources as $config )
		{
			$source = null;
			$name = $config['name'] ?? null;

			switch( $config['type'] ?? '' )
			{
				case 'yaml':
					if( isset( $config['path'] ) && file_exists( $config['path'] ) )
					{
						$source = new Yaml( $config['path'] );
					}
					break;

				case 'encrypted':
					if( isset( $config['path'], $config['key'] ) && file_exists( $config['path'] ) )
					{
						try
						{
							$source = new Encrypted( $config['path'], $config['key'] );
						}
						catch( \Exception $e )
						{
							// Skip if decryption fails
						}
					}
					break;

				case 'env':
					$source = new Env( \Neuron\Data\Env::getInstance() );
					break;
			}

			if( $source !== null )
			{
				$manager->addSource( $source, $name );
			}
		}

		return $manager;
	}

	/**
	 * Create a SettingManager for testing with in-memory configuration
	 *
	 * @param array $config Configuration array
	 * @return SettingManager
	 */
	public static function createForTesting( array $config ): SettingManager
	{
		$manager = new SettingManager();
		$manager->setSource( new \Neuron\Data\Settings\Source\Memory( $config ) );

		return $manager;
	}

	/**
	 * Get the standard configuration directory structure
	 *
	 * @param string $basePath Base path for configuration
	 * @return array
	 */
	public static function getExpectedStructure( string $basePath = 'config' ): array
	{
		$env = EnvironmentDetector::detect();

		return [
			'base_config' => $basePath . '/neuron.yaml',
			'environment_config' => $basePath . '/environments/' . $env . '.yaml',
			'base_secrets' => $basePath . '/secrets.yml.enc',
			'master_key' => $basePath . '/master.key',
			'environment_secrets' => $basePath . '/environments/' . $env . '.secrets.yml.enc',
			'environment_key' => $basePath . '/environments/' . $env . '.key',
		];
	}
}