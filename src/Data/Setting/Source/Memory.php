<?php

namespace Neuron\Data\Setting\Source;

/**
 * Internal array based setting source.
 */
class Memory implements ISettingSource
{
	private array $_Settings = array();

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
