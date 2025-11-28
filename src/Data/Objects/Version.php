<?php

namespace Neuron\Data\Objects;

/**
 * Object for loading/parsing version information.
 */
class Version
{
	public int $major;
	public int $minor;
	public int $patch;
	public int $build;

	/**
	 * Version constructor.
	 */

	public function __construct()
	{
		$this->major = 0;
		$this->minor = 0;
		$this->patch = 0;
		$this->build = 0;
	}

	public function __toString() : string
	{
		return $this->getAsString();
	}

	/**
	 * Returns the version as a string.
	 * @return string
	 */

	public function getAsString() : string
	{
		$release = "{$this->major}.{$this->minor}.{$this->patch}";
		if( $this->build )
		{
			$release .= " ({$this->build})";
		}

		return $release;
	}
}
