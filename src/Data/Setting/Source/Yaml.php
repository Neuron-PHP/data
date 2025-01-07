<?php

namespace Neuron\Data\Setting\Source;

use Neuron\Log\Log;
use Symfony\Component\Yaml\Yaml as YamlParser;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * .yaml file source.
 */
class Yaml implements ISettingSource
{
	private array $_Settings = array();

	public function __construct( $File )
	{
		if( !file_exists( $File ) )
		{
			throw new \Exception( "Setting\Source\Yaml Cannot open $File" );
		}

		try
		{
			$this->_Settings = YamlParser::parseFile( $File );
		}
		catch( ParseException $exception )
		{
			Log::error( "Failed to load schedule: ".$exception->getMessage() );
			return [];
		}
	}

	public function get( string $SectionName, string $Name ) : ?string
	{
		if( array_key_exists( $SectionName, $this->_Settings ) )
		{
			$Section = $this->_Settings[ $SectionName ];

			if( array_key_exists( $Name, $Section ) )
			{
				return $Section[ $Name ];
			}
		}
		return null;
	}

	public function set( string $SectionName, string $Name, string $Value ) : ISettingSource
	{
		$this->_Settings[ $SectionName ][ $Name ] = $Value;
		return $this;
	}

	public function getSectionNames() : array
	{
		return array_keys( $this->_Settings );
	}

	public function getSectionSettingNames( string $Section ) : array
	{
		return array_keys( $this->_Settings[ $Section ] );
	}

	public function save() : bool
	{
		return false;
	}
}
