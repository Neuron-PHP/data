<?php

namespace Neuron\Data\Setting\Source;

/**
 * Access to setting based services.
 */
interface ISettingSource
{
	public function get( string $sectionName, string $name ) : mixed;
	public function set( string $sectionName, string $name, mixed $value ) : ISettingSource;
	public function getSectionNames() : array;
	public function getSectionSettingNames( string $section ) : array;
	public function getSection( string $sectionName ) : ?array;
	public function save() : bool;
}
