<?php

namespace Neuron\Data;

class Env
{
	private static ?Env 		$instance = null;
	private 			?string	$_FileName;

	/**
	 * Env constructor.
	 * @param string|null $FileName
	 */
	private function __construct( string $FileName = null )
	{
		$this->_FileName = $FileName;

		if( is_null( $this->_FileName ) )
		{
			$this->_FileName = "{$_SERVER['DOCUMENT_ROOT']}/.env";
		}

		$this->loadEnvFile();
	}

	/**
	 * @param null $envFile
	 * @return Env|null
	 */
	public static function getInstance( $envFile = null ): ?Env
	{
		if ( is_null( self::$instance ) )
		{
			self::$instance = new self( $envFile );
		}

		return self::$instance;
	}

	/**
	 * @return void
	 */
	public function loadEnvFile(): void
	{
		if( !file_exists( $this->_FileName ) )
		{
			return;
		}

		$Configs = file( $this->_FileName );

		foreach( $Configs as $Config )
		{
			$Config = trim( str_replace( "\n", "", $Config ) );

			if( $Config && $Config[ 0 ] != '#')
			{
				$this->put( $Config );
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
	public function get( $key ): bool|array|string
	{
		return trim( getenv( trim( $key ) ) );
	}
}
