<?php

namespace Neuron\Data\Object;

/**
 * Object for loading/parsing version information.
 */
class Version
{
	public int $Major;
	public int $Minor;
	public int $Patch;
	public int $Build;

	/**
	 * Version constructor.
	 */

	public function __construct()
	{
		$this->Major = 0;
		$this->Minor = 0;
		$this->Patch = 0;
		$this->Build = 0;
	}

	/**
	 * Parses version information from a json string.
	 * @param string $Data
	 * @throws \Exception
	 */

	public function loadFromString( string $Data ): void
	{
		$Json = json_decode( $Data,true );

		if( $Json === null )
		{
			throw new \Exception( "Unable to parse json from '$Data'" );
		}

		$this->Major = $Json[ 'major' ];
		$this->Minor = $Json[ 'minor' ];
		$this->Patch = $Json[ 'patch' ];

		if( array_key_exists( 'build', $Json ) )
		{
			$this->Build = $Json[ 'build' ];
		}
	}

	/**
	 * Loads version information from a json file.
	 * @param string $File
	 * @throws \Exception
	 */

	public function loadFromFile( string $File = '.version.json' ): void
	{
		if( !file_exists( $File ) )
		{
			throw new \Exception( "Cannot find version file '$File'" );
		}

		$Data = file_get_contents( $File );

		$this->loadFromString( $Data );
	}

	/**
	 * Returns the version as a string.
	 * @return string
	 */

	public function getAsString() : string
	{
		$Release = "{$this->Major}.{$this->Minor}.{$this->Patch}";
		if( $this->Build )
		{
			$Release .= " ({$this->Build})";
		}

		return $Release;
	}
}
