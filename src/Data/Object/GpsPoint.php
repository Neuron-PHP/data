<?php

namespace Neuron\Data\Object;

/**
 * Object for holding lot/lon data.
 */

class GpsPoint
{
	public function __construct( float $Latitude = 0.0, float $Longitude = 0.0 )
	{
		$this->Latitude  = $Latitude;
		$this->Longitude = $Longitude;
	}

	public float $Latitude;
	public float $Longitude;
}
