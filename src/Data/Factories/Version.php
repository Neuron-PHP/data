<?php

namespace Neuron\Data\Factories;

use Neuron\Data\Objects;

class Version
{
	/**
	 * Loads version information from a json file.
	 * @param string $file
	 * @return Objects\Version
	 * @throws \Exception
	 */

	static public function fromFile( string $file = '.version.json' ): Objects\Version
	{
		if( !file_exists( $file ) )
		{
			throw new \Exception( "Cannot find version file '$file'" );
		}

		$data = file_get_contents( $file );

		return self::fromString( $data );
	}

	/**
	 * Parses version information from a json string.
	 * @param string $data
	 * @return Objects\Version
	 * @throws \Exception
	 */

	public static function fromString( string $data ): Objects\Version
	{
		$version = new Objects\Version();

		$json = json_decode( $data,true );

		if( $json === null )
		{
			throw new \Exception( "Unable to parse json from '$data'" );
		}

		$version->major = $json[ 'major' ];
		$version->minor = $json[ 'minor' ];
		$version->patch = $json[ 'patch' ];

		if( array_key_exists( 'build', $json ) )
		{
			$version->build = $json[ 'build' ];
		}

		return $version;
	}
}
