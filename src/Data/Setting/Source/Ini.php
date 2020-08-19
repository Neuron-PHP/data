<?php

namespace Neuron\Data\Setting\Source;

class Ini implements ISettingSource
{
	private array $_Settings = array();

	public function __construct( $sFile )
	{
		if( !file_exists( $sFile ) )
		{
			throw new \Exception( "Setting\Source\Ini Cannot open $sFile" );
		}

		$this->_Settings = parse_ini_file( $sFile, true );
	}

	public function get( $sSection, $sName)
	{
		if( array_key_exists( $sSection, $this->_Settings ) )
		{
			$aSection = $this->_Settings[ $sSection ];

			if( array_key_exists( $sName, $aSection ) )
			{
				return $aSection[ $sName ];
			}
		}
		return false;
	}

	public function set( $sSection, $sName, $sValue)
	{
		$this->_Settings[ $sSection ][ $sName ] = $sValue;
	}

	public function getSectionNames()
	{
		return array_keys( $this->_Settings );
	}

	public function getSectionSettingNames( $sSection )
	{
		return array_keys( $this->_Settings[ $sSection ] );
	}

	public function save()
	{
		return false;
	}
}
