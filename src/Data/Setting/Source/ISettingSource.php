<?php

namespace Neuron\Data\Setting\Source;

interface ISettingSource
{
	public function get( $sSection, $sName );
	public function set( $sName, $sSection, $sValue );
	public function getSectionNames();
	public function getSectionSettingNames( $sSection );

	public function save();
}
