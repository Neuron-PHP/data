<?php

namespace Neuron\Data\Setting;

use Neuron\Data\Setting\Source\ISettingSource;

/**
 * Generic settings manager. Allows generic interaction with settings from different sources such as .ini, .yaml etc.
 */
class SettingManager
{
	private ISettingSource $source;
	private ?ISettingSource $fallback = null;

	/**
	 * @param ISettingSource $source
	 */

	public function __construct( ISettingSource $source )
	{
		$this->setSource( $source );
	}

	/**
	 * @return mixed
	 */

	public function getSource() : ISettingSource
	{
		return $this->source;
	}

	/**
	 * @param ISettingSource $source
	 * @return SettingManager
	 */

	public function setSource( ISettingSource $source ) : SettingManager
	{
		$this->source = $source;
		return $this;
	}

	/**
	 * @return ISettingSource|null
	 */

	public function getFallback(): ?ISettingSource
	{
		return $this->fallback;
	}

	/**
	 * @param ISettingSource $fallback
	 * @return SettingManager
	 */

	public function setFallback( ISettingSource $fallback ): SettingManager
	{
		$this->fallback = $fallback;
		return $this;
	}



	/**
	 * @param string $section
	 * @param string $name
	 * @return string|null
	 */

	public function get( string $section, string $name )
	{
		$value = $this->getSource()->get( $section, $name );

		if( $value )
		{
			return $value;
		}

		return $this->getFallback()
						?->get( $section, $name );
	}

	/**
	 * @param string $section
	 * @param string $name
	 * @param string $value
	 */

	public function set( string $section, string $name, string $value )
	{
		$this->getSource()->set( $section, $name, $value );
		$this->getSource()->save();
	}

	/**
	 * @return array
	 */

	public function getSectionNames() : array
	{
		return $this->getSource()->getSectionNames();
	}

	/**
	 * @param string $section
	 * @return array
	 */

	public function getSectionSettingNames( string $section ) : array
	{
		return $this->getSource()->getSectionSettingNames( $section );
	}

	/**
	 * Get entire section as an array
	 *
	 * @param string $section
	 * @return array|null
	 */

	public function getSection( string $section ) : ?array
	{
		$value = $this->getSource()->getSection( $section );

		if( $value )
		{
			return $value;
		}

		return $this->getFallback()
						?->getSection( $section );
	}
}
