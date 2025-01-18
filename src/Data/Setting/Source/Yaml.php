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
	private array $_Settings = array();

	/**
	 * @throws \Exception
	 */
	public function __construct( $File )
	{
		if( !file_exists( $File ) )
		{
			throw new \Exception( "Setting\Source\Yaml Cannot find $File" );
		}

		try
		{
			$this->_Settings = YamlParser::parseFile( $File );
		}
		catch( ParseException $exception )
		{
			throw new \Exception( "Setting\Source\Yaml Cannot parse $File" );
		}
	}

	/**
	 * @param string $SectionName
	 * @param string $Name
	 * @return string|null
	 */
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

	/**
	 * @param string $SectionName
	 * @param string $Name
	 * @param string $Value
	 * @return ISettingSource
	 */
	public function set( string $SectionName, string $Name, string $Value ) : ISettingSource
	{
		$this->_Settings[ $SectionName ][ $Name ] = $Value;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getSectionNames() : array
	{
		return array_keys( $this->_Settings );
	}

	/**
	 * @param string $Section
	 * @return array
	 */
	public function getSectionSettingNames( string $Section ) : array
	{
		return array_keys( $this->_Settings[ $Section ] );
	}

	/**
	 * @return bool
	 */
	public function save() : bool
	{
		return false;
	}
}
