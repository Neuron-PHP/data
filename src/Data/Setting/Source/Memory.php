<?php

namespace Neuron\Data\Setting\Source;

/**
 * Internal array based setting source.
 */
class Memory implements ISettingSource
{
	private array $_Settings = array();

	public function get( string $SectionName, string $Name )
	{
		if( array_key_exists( $SectionName, $this->_Settings ) )
		{
			$Section = $this->_Settings[ $SectionName ];

			if( array_key_exists( $Name, $Section ) )
			{
				return $Section[ $Name ];
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
