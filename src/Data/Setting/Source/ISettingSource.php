<?php

namespace Neuron\Data\Setting\Source;

/**
 * Access to setting based services.
 */
interface ISettingSource
{
	public function get( string $SectionName, string $Name ) : ?string;
	public function set( string $SectionName, string $Name, string $Value ) : ISettingSource;
	public function getSectionNames() : array;
	public function getSectionSettingNames( string $Section ) : array;
	public function getSection( string $SectionName ) : ?array;
	public function save() : bool;
}
