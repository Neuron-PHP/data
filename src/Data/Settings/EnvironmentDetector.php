<?php

namespace Neuron\Data\Settings;

/**
 * Detects the current application environment
 *
 * Provides consistent environment detection across the framework.
 * Checks various sources (environment variables, server variables)
 * to determine if the application is running in development, test,
 * staging, or production.
 *
 * @package Neuron\Data\Settings
 */
class EnvironmentDetector
{
	/**
	 * Valid environment names
	 */
	private const VALID_ENVIRONMENTS = [
		'development',
		'test',
		'staging',
		'production'
	];

	/**
	 * Environment variable names to check (in priority order)
	 */
	private const ENV_VARIABLES = [
		'APP_ENV',
		'NEURON_ENV',
		'APPLICATION_ENV',
		'ENVIRONMENT'
	];

	/**
	 * Detect the current environment
	 *
	 * @return string One of: development, test, staging, production
	 */
	public static function detect(): string
	{
		// Check environment variables in priority order
		foreach( self::ENV_VARIABLES as $varName )
		{
			// Check $_ENV first
			if( isset( $_ENV[$varName] ) )
			{
				$env = self::normalizeEnvironment( $_ENV[$varName] );
				if( $env !== null )
				{
					return $env;
				}
			}

			// Check $_SERVER as fallback
			if( isset( $_SERVER[$varName] ) )
			{
				$env = self::normalizeEnvironment( $_SERVER[$varName] );
				if( $env !== null )
				{
					return $env;
				}
			}

			// Check getenv as last resort
			$value = getenv( $varName );
			if( $value !== false )
			{
				$env = self::normalizeEnvironment( $value );
				if( $env !== null )
				{
					return $env;
				}
			}
		}

		// Check for common development indicators
		if( self::isDevelopmentEnvironment() )
		{
			return 'development';
		}

		// Default to development for safety
		// (production should always be explicitly set)
		return 'development';
	}

	/**
	 * Normalize environment name to standard format
	 *
	 * @param string $environment Raw environment name
	 * @return string|null Normalized environment or null if invalid
	 */
	private static function normalizeEnvironment( string $environment ): ?string
	{
		$normalized = strtolower( trim( $environment ) );

		// Direct match
		if( in_array( $normalized, self::VALID_ENVIRONMENTS, true ) )
		{
			return $normalized;
		}

		// Common aliases
		$aliases = [
			'dev' => 'development',
			'develop' => 'development',
			'local' => 'development',
			'testing' => 'test',
			'tests' => 'test',
			'stage' => 'staging',
			'prod' => 'production',
			'live' => 'production'
		];

		if( isset( $aliases[$normalized] ) )
		{
			return $aliases[$normalized];
		}

		return null;
	}

	/**
	 * Check for common development environment indicators
	 *
	 * @return bool
	 */
	private static function isDevelopmentEnvironment(): bool
	{
		// Check for localhost
		if( isset( $_SERVER['HTTP_HOST'] ) )
		{
			$host = strtolower( $_SERVER['HTTP_HOST'] );
			if( $host === 'localhost' ||
			    strpos( $host, 'localhost:' ) === 0 ||
			    $host === '127.0.0.1' ||
			    strpos( $host, '127.0.0.1:' ) === 0 ||
			    strpos( $host, '.local' ) !== false ||
			    strpos( $host, '.test' ) !== false )
			{
				return true;
			}
		}

		// Check for common development tools
		if( isset( $_SERVER['PHP_IDE_CONFIG'] ) || // PhpStorm
		    isset( $_ENV['XDEBUG_CONFIG'] ) ||      // Xdebug
		    isset( $_ENV['PHP_IDE_CONFIG'] ) )
		{
			return true;
		}

		// Check if running from CLI (often development/testing)
		if( PHP_SAPI === 'cli' && !isset( $_ENV['CI'] ) )
		{
			return true;
		}

		return false;
	}

	/**
	 * Check if current environment is production
	 *
	 * @return bool
	 */
	public static function isProduction(): bool
	{
		return self::detect() === 'production';
	}

	/**
	 * Check if current environment is development
	 *
	 * @return bool
	 */
	public static function isDevelopment(): bool
	{
		return self::detect() === 'development';
	}

	/**
	 * Check if current environment is test
	 *
	 * @return bool
	 */
	public static function isTest(): bool
	{
		return self::detect() === 'test';
	}

	/**
	 * Check if current environment is staging
	 *
	 * @return bool
	 */
	public static function isStaging(): bool
	{
		return self::detect() === 'staging';
	}

	/**
	 * Get all valid environment names
	 *
	 * @return array
	 */
	public static function getValidEnvironments(): array
	{
		return self::VALID_ENVIRONMENTS;
	}

	/**
	 * Check if an environment name is valid
	 *
	 * @param string $environment
	 * @return bool
	 */
	public static function isValidEnvironment( string $environment ): bool
	{
		return in_array( strtolower( trim( $environment ) ), self::VALID_ENVIRONMENTS, true );
	}
}