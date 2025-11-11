<?php

namespace Neuron\Data\Object;

/**
 * Object for loading/parsing version information.
 */
class Version
{
	public int $major;
	public int $minor;
	public int $patch;
	public int $build;

	/**
	 * Version constructor.
	 */

	public function __construct()
	{
		$this->major = 0;
		$this->minor = 0;
		$this->patch = 0;
		$this->build = 0;
	}

	/**
	 * Parses version information from a json string.
	 * @param string $data
	 * @throws \Exception
	 */

	public function loadFromString( string $data ): void
	{
		$json = json_decode( $data,true );

		if( $json === null )
		{
			throw new \Exception( "Unable to parse json from '$data'" );
		}

		$this->major = $json[ 'major' ];
		$this->minor = $json[ 'minor' ];
		$this->patch = $json[ 'patch' ];

		if( array_key_exists( 'build', $json ) )
		{
			$this->build = $json[ 'build' ];
		}
	}

	/**
	 * Loads version information from a json file.
	 * @param string $file
	 * @throws \Exception
	 */

	public function loadFromFile( string $file = '.version.json' ): void
	{
		if( !file_exists( $file ) )
		{
			throw new \Exception( "Cannot find version file '$file'" );
		}

		$data = file_get_contents( $file );

		$this->loadFromString( $data );
	}

	/**
	 * Returns the version as a string.
	 * @return string
	 */

	public function getAsString() : string
	{
		$release = "{$this->major}.{$this->minor}.{$this->patch}";
		if( $this->build )
		{
			$release .= " ({$this->build})";
		}

		return $release;
	}
}
