<?php

namespace Neuron\Data\Settings\Source;

use Neuron\Core\System\IFileSystem;
use Neuron\Core\System\RealFileSystem;

/**
 * .ini file based settings.
 */
class Ini implements ISettingSource
{
	private array $settings = array();
	private IFileSystem $fs;

	/**
	 * Ini constructor.
	 * @param string $file Path to INI file
	 * @param IFileSystem|null $fs File system implementation (null = use real file system)
	 * @throws \Exception
	 */
	public function __construct( string $file, ?IFileSystem $fs = null )
	{
		$this->fs = $fs ?? new RealFileSystem();

		if( !$this->fs->fileExists( $file ) )
		{
			throw new \Exception( "Setting\Source\Ini Cannot open $file" );
		}

		$content = $this->fs->readFile( $file );

		if( $content === false )
		{
			throw new \Exception( "Setting\Source\Ini Cannot read $file" );
		}

		$this->settings = parse_ini_string( $content, true );
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
