<?php

namespace Neuron\Data\Objects;

/**
 * Object for holding lot/lon data.
 */

class GpsPoint
{
	/**
	 * @param float $latitude
	 * @param float $longitude
	 */

	public function __construct( float $latitude = 0.0, float $longitude = 0.0 )
	{
		$this->latitude  = $latitude;
		$this->longitude = $longitude;
	}

	public float $latitude;
	public float $longitude;
}
