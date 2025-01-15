<?php

namespace Neuron\Data\Setting;

use Neuron\Data\Setting\Source\Env;
use Neuron\Data\Setting\Source\ISettingSource;

/**
 * Generic settings manager. Allows generic interaction with settings from different sources such as .ini, .yaml etc.
 */
class SettingManager
{
	private ISettingSource $_Source;
	private ?ISettingSource $_Fallback = null;

	/**
	 * @param ISettingSource $Source
	 */

	public function __construct( ISettingSource $Source )
	{
		$this->setSource( $Source );
	}

	/**
	 * @return mixed
	 */

	public function getSource() : ISettingSource
	{
		return $this->_Source;
	}

	/**
	 * @param ISettingSource $Source
	 */

	public function setSource( ISettingSource $Source ) : SettingManager
	{
		$this->_Source = $Source;
		return $this;
	}

	/**
	 * @return ISettingSource
	 */
	public function getFallback(): ?ISettingSource
	{
		return $this->_Fallback;
	}

	/**
	 * @param ISettingSource $Fallback
	 * @return SettingManager
	 */
	public function setFallback( ISettingSource $Fallback ): SettingManager
	{
		$this->_Fallback = $Fallback;
		return $this;
	}



	/**
	 * @param string $Section
	 * @param string $Name
	 * @return mixed
	 */

	public function get( string $Section, string $Name )
	{
		$Value = $this->getSource()->get( $Section, $Name );

		if( $Value )
		{
			return $Value;
		}

		return $this->getFallback()
						?->get( $Section, $Name );
	}

	/**
	 * @param string $Section
	 * @param string $Name
	 * @param string $Value
	 */

	public function set( string $Section, string $Name, string $Value )
	{
		$this->getSource()->set( $Section, $Name, $Value );
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
	 * @param string $Section
	 * @return array
	 */

	public function getSectionSettingNames( string $Section ) : array
	{
		return $this->getSource()->getSectionSettingNames( $Section );
	}
}
