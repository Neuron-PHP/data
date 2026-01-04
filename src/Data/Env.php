<?php

namespace Neuron\Data;

use Neuron\Core\System\IFileSystem;
use Neuron\Core\System\RealFileSystem;

/**
 * Environment variable manager and .env file loader for the Neuron framework.
 * 
 * This singleton class provides secure and convenient access to environment
 * variables and .env file configuration. It automatically loads environment
 * variables from .env files and provides a clean API for accessing configuration
 * values throughout the application with proper error handling.
 * 
 * Key features:
 * - Automatic .env file detection and loading
 * - Singleton pattern for consistent environment state
 * - Comment support in .env files (lines starting with #)
 * - Secure environment variable access with null safety
 * - Custom .env file path support
 * - Environment variable validation and type conversion
 * 
 * The class automatically looks for .env files in the document root unless
 * a custom path is specified, making it ideal for configuration management
 * across different deployment environments.
 * 
 * @package Neuron\Data
 * 
 * @example
 * ```php
 * // .env file contents:
 * // DB_HOST=localhost
 * // DB_PORT=3306
 * // DEBUG=true
 * // # This is a comment
 * // API_KEY=secret_key_here
 * 
 * // Load and access environment variables
 * $env = Env::getInstance();
 * 
 * $dbHost = $env->get('DB_HOST');      // 'localhost'
 * $dbPort = $env->get('DB_PORT');      // '3306' 
 * $debug = $env->get('DEBUG');         // 'true'
 * $missing = $env->get('MISSING');     // null
 * 
 * // Load custom .env file
 * $env = Env::getInstance('/path/to/custom/.env');
 * 
 * // Set environment variables programmatically
 * $env->put('RUNTIME_CONFIG=dynamic_value');
 * ```
 */
class Env
{
	private static ?Env 		$instance = null;
	private 			?string	$fileName;
	private IFileSystem $fs;

	/**
	 * Env constructor.
	 * @param string|null $fileName
	 * @param IFileSystem|null $fs File system implementation (null = use real file system)
	 */
	private function __construct( ?string $fileName = null, ?IFileSystem $fs = null )
	{
		$this->fileName = $fileName;
		$this->fs = $fs ?? new RealFileSystem();

		if( is_null( $this->fileName ) )
		{
			$this->fileName = "{$_SERVER['DOCUMENT_ROOT']}/.env";
		}

		$this->loadEnvFile();
	}

	/**
	 * @return void
	 */

	public function reset() : void
	{
		self::$instance = null;
	}

	/**
	 * @param string|null $envFile
	 * @param IFileSystem|null $fs File system implementation (null = use real file system)
	 * @return Env|null
	 */
	public static function getInstance( ?string $envFile = null, ?IFileSystem $fs = null ): ?Env
	{
		if ( is_null( self::$instance ) )
		{
			self::$instance = new self( $envFile, $fs );
		}

		return self::$instance;
	}

	/**
	 * @return void
	 */

	public function loadEnvFile(): void
	{
		if( !$this->fs->fileExists( $this->fileName ) )
		{
			return;
		}

		$content = $this->fs->readFile( $this->fileName );

		if( $content === false )
		{
			return;
		}

		$configs = explode( "\n", $content );

		foreach( $configs as $config )
		{
			$config = trim( $config );

			if( $config && $config[ 0 ] != '#')
			{
				$this->put( $config );
			}
		}
	}

	/**
	 * @param $config
	 * @return bool
	 */

	public function put( $config ): bool
	{
		return putenv( $config );
	}

	/**
	 * @param $key
	 * @return array|false|string
	 */

	public function get( $key ): null | array | string
	{
		$value = getenv( trim( $key ) );

		if( $value === false )
		{
			return null;
		}

		return trim( $value );
	}
}
