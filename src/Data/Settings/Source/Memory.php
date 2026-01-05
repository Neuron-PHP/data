<?php

namespace Neuron\Data\Settings\Source;

/**
 * Internal array based setting source.
 */
class Memory implements ISettingSource
{
	private array $settings = array();

	/**
	 * Constructor
	 *
	 * @param array $config Initial configuration data organized by sections
	 */
	public function __construct( array $config = [] )
	{
		// Ensure all values are properly structured as section => settings
		foreach( $config as $section => $settings )
		{
			if( is_array( $settings ) )
			{
				$this->settings[$section] = $settings;
			}
			else
			{
				// If a scalar value is provided, wrap it
				$this->settings[$section] = ['value' => $settings];
			}
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
