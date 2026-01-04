<?php

namespace Neuron\Data\Factories;

use Neuron\Core\System\IFileSystem;
use Neuron\Core\System\RealFileSystem;
use Neuron\Data\Objects;

class Version
{
	/**
	 * Loads version information from a json file.
	 * @param string $file
	 * @param IFileSystem|null $fs File system implementation (null = use real file system)
	 * @return Objects\Version
	 * @throws \Exception
	 */

	static public function fromFile( string $file = '.version.json', ?IFileSystem $fs = null ): Objects\Version
	{
		$fs = $fs ?? new RealFileSystem();

		if( !$fs->fileExists( $file ) )
		{
			throw new \Exception( "Cannot find version file '$file'" );
		}

		$data = $fs->readFile( $file );

		if( $data === false )
		{
			throw new \Exception( "Cannot read version file '$file'" );
		}

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
