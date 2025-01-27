<?php

namespace Neuron\Data\Setting\Source;

/**
 * .ini file based settings.
 */
class Ini implements ISettingSource
{
	private array $_Settings = array();

	/**
	 * Ini constructor.
	 * @param $File
	 * @throws \Exception
	 */

	public function __construct( $File )
	{
		if( !file_exists( $File ) )
		{
			throw new \Exception( "Setting\Source\Ini Cannot open $File" );
		}

		$this->_Settings = parse_ini_file( $File, true );
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
		// @todo: Implement saving.
		return false;
	}
}
