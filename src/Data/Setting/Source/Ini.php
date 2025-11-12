<?php

namespace Neuron\Data\Setting\Source;

/**
 * .ini file based settings.
 */
class Ini implements ISettingSource
{
	private array $settings = array();

	/**
	 * Ini constructor.
	 * @param $file
	 * @throws \Exception
	 */

	public function __construct( $file )
	{
		if( !file_exists( $file ) )
		{
			throw new \Exception( "Setting\Source\Ini Cannot open $file" );
		}

		$this->settings = parse_ini_file( $file, true );
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
		// @todo: Implement saving.
		return false;
	}
}
