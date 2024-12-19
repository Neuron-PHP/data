<?php

namespace Neuron\Data;

class Env
{
	private static $instance = null;

	/**
	 * @param string $File
	 */
	private function __construct( string $File = null)
	{
		if( file_exists( $File ) )
		{
			$this->loadEnvFile( $File );
		}
	}

	/**
	 * @param null $envFile
	 * @return Env|null
	 */
	public static function getInstance( $envFile = null )
	{
		if ( is_null( self::$instance ) )
		{
			self::$instance = new self( $envFile );
		}

		return self::$instance;
	}

	/**
	 * @param string $File
	 */
	public function loadEnvFile( string $File )
	{
		if ( is_null( $File ) )
		{
			$File = "{$_SERVER['DOCUMENT_ROOT']}/.env";
		}

		$envConfigsArr = file( $File );

		foreach ( $envConfigsArr as $config )
		{
			$config = str_replace( "\n", "", $config );

			if( $config )
			{
				$this->put( $config );
			}
		}
	}

	/**
	 * @param $config
	 */
	public function put( $config )
	{
		if( $config[0] != '#' )
		{
			putenv( $config );
		}
	}

	/**
	 * @param $key
	 * @return array|false|string
	 */
	public function get( $key )
	{
		return trim( getenv( $key ) );
	}
}
