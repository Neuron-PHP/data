<?php

namespace Neuron\Data\Setting\Source;

use Neuron\Log\Log;
use Symfony\Component\Yaml\Yaml as YamlParser;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * .yaml based setting source.
 */
class Yaml implements ISettingSource
{
	private array $settings = array();

	/**
	 * @throws \Exception
	 */

	public function __construct( $file )
	{
		if( !file_exists( $file ) )
		{
			throw new \Exception( "Setting\Source\Yaml Cannot find $file" );
		}

		try
		{
			$this->settings = YamlParser::parseFile( $file );
		}
		catch( ParseException $exception )
		{
			throw new \Exception( "Setting\Source\Yaml Cannot parse $file" );
		}
	}

	/**
	 * @param string $sectionName
	 * @param string $name
	 * @return mixed
	 */

	public function get( string $sectionName, string $name ) : mixed
	{
		if( array_key_exists( $sectionName, $this->settings ) )
		{
			$section = $this->settings[ $sectionName ];

			if( array_key_exists( $name, $section ) )
			{
				return $section[ $name ];
			}
		}

		return null;
	}

	/**
	 * @param string $sectionName
	 * @param string $name
	 * @param mixed $value
	 * @return ISettingSource
	 */

	public function set( string $sectionName, string $name, mixed $value ) : ISettingSource
	{
		$this->settings[ $sectionName ][ $name ] = $value;
		return $this;
	}

	/**
	 * @return array
	 */

	public function getSectionNames() : array
	{
		return array_keys( $this->settings );
	}

	/**
	 * @param string $section
	 * @return array
	 */

	public function getSectionSettingNames( string $section ) : array
	{
		return array_keys( $this->settings[ $section ] );
	}

	/**
	 * Get entire section as an array
	 *
	 * @param string $sectionName
	 * @return array|null
	 */

	public function getSection( string $sectionName ) : ?array
	{
		return $this->settings[ $sectionName ] ?? null;
	}

	/**
	 * @return bool
	 */

	public function save() : bool
	{
		return false;
	}
}
