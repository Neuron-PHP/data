<?php

namespace Neuron\Data\Setting\Source;

/**
 * environment variable based settings.
 */
class Env implements ISettingSource
{
	private \Neuron\Data\Env $env;

	public function __construct( \Neuron\Data\Env $env )
	{
		$this->env = $env;
	}

	/**
	 * This method is used to get the value of a setting stored in an environment variable.
	 * The section and name will be concatenated with an underscore and the value will be
	 * uppercased.
	 * e.g. get( 'test', 'name' ) will look for the environment variable TEST_NAME.
	 *
	 * @param string $sectionName
	 * @param string $name
	 * @return mixed
	 */
	public function get( string $sectionName, string $name ): mixed
	{
		$sectionName = strtoupper( $sectionName );
		$name = strtoupper( $name );
		return $this->env->get( "{$sectionName}_{$name}" );
	}

	/**
	 * This method is used to set the value of a setting stored in an environment variable.
	 * The section and name will be concatenated with an underscore and the value will be
	 * uppercased.
	 * e.g. set( 'test', 'name', 'value' ) will set the environment variable TEST_NAME=value.
	 *
	 * @param string $sectionName
	 * @param string $name
	 * @param mixed $value
	 * @return ISettingSource
	 */

	public function set( string $sectionName, string $name, mixed $value ): ISettingSource
	{
		$sectionName = strtoupper( $sectionName );
		$name = strtoupper( $name );

		$this->env->put( "{$sectionName}_{$name}=$value" );

		return $this;
	}

	/**
	 * This method is used to get the section names.
	 * For environment variables we can infer section names by scanning environment
	 * keys that contain an underscore and returning the prefix before the underscore.
	 *
	 * @return array
	 */

	public function getSectionNames(): array
	{
		$keys = array_merge( array_keys( $_ENV ?? [] ), array_keys( $_SERVER ?? [] ) );

		$sections = [];
		foreach( $keys as $key )
		{
			if( !is_string( $key ) )
			{
				continue;
			}

			$keyUpper = strtoupper( $key );
			$pos = strpos( $keyUpper, '_' );
			if( $pos !== false && $pos > 0 )
			{
				$section = substr( $keyUpper, 0, $pos );
				$sections[$section] = true;
			}
		}

		return array_values( array_keys( $sections ) );
	}

	/**
	 * This method is used to get the setting names for a section.
	 * We scan environment variable keys for those starting with SECTIONNAME_ and
	 * return the suffix names in lowercase (matching other sources' expectations).
	 *
	 * @param string $section
	 * @return array
	 */

	public function getSectionSettingNames( string $section ): array
	{
		$section = strtoupper( $section );
		$keys = array_merge( array_keys( $_ENV ?? [] ), array_keys( $_SERVER ?? [] ) );

		$names = [];
		$prefix = $section . '_';
		$prefixLength = strlen( $prefix );

		foreach( $keys as $key )
		{
			if( !is_string( $key ) )
			{
				continue;
			}

			$keyUpper = strtoupper( $key );
			if( strncmp( $keyUpper, $prefix, $prefixLength ) === 0 )
			{
				$suffix = substr( $keyUpper, $prefixLength );
				if( $suffix !== '' )
				{
					$names[] = strtolower( $suffix );
				}
			}
		}

		// unique and preserve order
		$names = array_values( array_unique( $names ) );

		return $names;
	}

	/**
	 * Get the entire section as an array.
	 * For environment variables we scan matching keys and return an associative array
	 * of name => value. Returns null if a section not present.
	 *
	 * @param string $sectionName
	 * @return array|null
	 */

	public function getSection( string $sectionName ): ?array
	{
		$section = strtoupper( $sectionName );
		$names = $this->getSectionSettingNames( $section );

		if( empty( $names ) )
		{
			return null;
		}

		$config = [];
		foreach( $names as $name )
		{
			$key = strtoupper( $section . '_' . $name );
			$value = $this->env->get( $key );
			if( $value !== null )
			{
				$config[$name] = $value;
			}
		}

		return $config;
	}

	/**
	 * This method is used to save the settings to the environment variables.
	 * This is not possible, so this method will always return false.
	 *
	 * @return bool
	 */

	public function save(): bool
	{
		return false;
	}
}
