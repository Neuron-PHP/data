<?php

namespace Neuron\Data\Settings;

use Neuron\Data\Settings\Source\ISettingSource;

/**
 * Enhanced settings manager with support for multiple ordered sources
 *
 * Maintains backward compatibility with single source + fallback pattern
 * while also supporting multiple ordered sources for layered configuration.
 *
 * @package Neuron\Data\Settings
 */
class SettingManager implements ISettingSource
{
	private ?ISettingSource $source = null;
	private ?ISettingSource $fallback = null;

	/**
	 * @var array<array{source: ISettingSource, name: ?string}> Additional ordered sources
	 */
	private array $additionalSources = [];

	/**
	 * @param ISettingSource|null $source Primary source
	 */
	public function __construct( ?ISettingSource $source = null )
	{
		if( $source !== null )
		{
			$this->setSource( $source );
		}
	}

	/**
	 * Get the primary source
	 *
	 * @return ISettingSource|null
	 */
	public function getSource(): ?ISettingSource
	{
		return $this->source;
	}

	/**
	 * Set the primary source
	 *
	 * @param ISettingSource $source
	 * @return SettingManager
	 */
	public function setSource( ISettingSource $source ): SettingManager
	{
		$this->source = $source;
		return $this;
	}

	/**
	 * Get the fallback source
	 *
	 * @return ISettingSource|null
	 */
	public function getFallback(): ?ISettingSource
	{
		return $this->fallback;
	}

	/**
	 * Set the fallback source
	 *
	 * @param ISettingSource $fallback
	 * @return SettingManager
	 */
	public function setFallback( ISettingSource $fallback ): SettingManager
	{
		$this->fallback = $fallback;
		return $this;
	}

	/**
	 * Add an additional source to the stack
	 * Sources added later have higher priority
	 *
	 * @param ISettingSource $source The setting source to add
	 * @param string|null $name Optional name for debugging
	 * @return SettingManager Fluent interface
	 */
	public function addSource( ISettingSource $source, ?string $name = null ): SettingManager
	{
		$this->additionalSources[] = ['source' => $source, 'name' => $name];
		return $this;
	}


	/**
	 * Get all configured sources in priority order
	 *
	 * @return array<ISettingSource> Sources from lowest to highest priority
	 */
	private function getAllSources(): array
	{
		$sources = [];

		// Fallback is lowest priority
		if( $this->fallback !== null )
		{
			$sources[] = $this->fallback;
		}

		// Primary source is next
		if( $this->source !== null )
		{
			$sources[] = $this->source;
		}

		// Additional sources in order (highest priority)
		foreach( $this->additionalSources as $sourceInfo )
		{
			$sources[] = $sourceInfo['source'];
		}

		return $sources;
	}

	/**
	 * Recursively merge two arrays (deep merge)
	 * Values from $override replace values from $base
	 *
	 * @param array $base Base array
	 * @param array $override Array to merge over base
	 * @return array Merged result
	 */
	private static function deepMerge( array $base, array $override ): array
	{
		$result = $base;

		foreach( $override as $key => $value )
		{
			if( is_array( $value ) && isset( $result[$key] ) && is_array( $result[$key] ) )
			{
				// Both are arrays - recursively merge
				$result[$key] = self::deepMerge( $result[$key], $value );
			}
			else
			{
				// Override takes precedence
				$result[$key] = $value;
			}
		}

		return $result;
	}

	/**
	 * Get a setting value from the highest priority source that has it
	 *
	 * @param string $section Section name
	 * @param string $name Setting name
	 * @return mixed The setting value or null if not found
	 */
	public function get( string $section, string $name ): mixed
	{
		// Check sources in reverse order (highest priority first)
		$sources = $this->getAllSources();
		foreach( array_reverse( $sources ) as $source )
		{
			$value = $source->get( $section, $name );
			if( $value !== null )
			{
				return $value;
			}
		}

		return null;
	}

	/**
	 * Set a setting value in the primary source (or last additional source if no primary)
	 *
	 * @param string $sectionName Section name
	 * @param string $name Setting name
	 * @param mixed $value Setting value
	 * @return ISettingSource Fluent interface
	 */
	public function set( string $sectionName, string $name, mixed $value ): ISettingSource
	{
		// Prefer additional sources (highest priority)
		if( !empty( $this->additionalSources ) )
		{
			$lastSource = $this->additionalSources[count( $this->additionalSources ) - 1]['source'];
			$lastSource->set( $sectionName, $name, $value );
			$lastSource->save();
			return $this;
		}

		// Fall back to primary source
		if( $this->source !== null )
		{
			$this->source->set( $sectionName, $name, $value );
			$this->source->save();
			return $this;
		}

		// Last resort: fallback source
		if( $this->fallback !== null )
		{
			$this->fallback->set( $sectionName, $name, $value );
			$this->fallback->save();
			return $this;
		}

		throw new \RuntimeException( 'No sources configured. Add a source before setting values.' );
	}

	/**
	 * Get all unique section names from all sources
	 *
	 * @return array
	 */
	public function getSectionNames(): array
	{
		$sections = [];
		$sources = $this->getAllSources();

		foreach( $sources as $source )
		{
			$sourceSections = $source->getSectionNames();
			foreach( $sourceSections as $section )
			{
				$sections[$section] = true;
			}
		}

		return array_keys( $sections );
	}

	/**
	 * Get all unique setting names for a section from all sources
	 *
	 * @param string $section Section name
	 * @return array
	 */
	public function getSectionSettingNames( string $section ): array
	{
		$names = [];
		$sources = $this->getAllSources();

		foreach( $sources as $source )
		{
			$sourceNames = $source->getSectionSettingNames( $section );
			foreach( $sourceNames as $name )
			{
				$names[$name] = true;
			}
		}

		return array_keys( $names );
	}

	/**
	 * Get entire section as an array, merging from all sources
	 *
	 * @param string $section Section name
	 * @return array|null Merged section data or null if section doesn't exist
	 */
	public function getSection( string $section ): ?array
	{
		$merged = null;
		$sources = $this->getAllSources();

		// Merge sections from all sources (lowest to highest priority)
		foreach( $sources as $source )
		{
			$sourceSection = $source->getSection( $section );
			if( $sourceSection !== null )
			{
				if( $merged === null )
				{
					$merged = $sourceSection;
				}
				else
				{
					// Deep merge arrays, with later sources overriding earlier ones
					$merged = self::deepMerge( $merged, $sourceSection );
				}
			}
		}

		return $merged;
	}

	/**
	 * Save all saveable sources
	 *
	 * @return bool True if all saves succeeded
	 */
	public function save(): bool
	{
		$success = true;
		$sources = $this->getAllSources();

		foreach( $sources as $source )
		{
			if( !$source->save() )
			{
				$success = false;
			}
		}

		return $success;
	}
}
