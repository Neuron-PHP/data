<?php

namespace Neuron\Data\Setting\Source;

/**
 * .ini file source.
 */
class Ini implements ISettingSource
{
	private array $_Settings = array();

	public function __construct( $File )
	{
		if( !file_exists( $File ) )
		{
			throw new \Exception( "Setting\Source\Ini Cannot open $File" );
		}

		$this->_Settings = parse_ini_file( $File, true );
	}

	public function get( string $Section, string $Name ) : bool
	{
		if( array_key_exists( $Section, $this->_Settings ) )
		{
			$aSection = $this->_Settings[ $Section ];

			if( array_key_exists( $Name, $aSection ) )
			{
				return $aSection[ $Name ];
			}
		}
		return false;
	}

	public function set( string $Section, string $Name, string $Value ) : ISettingSource
	{
		$this->_Settings[ $Section ][ $Name ] = $Value;
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
