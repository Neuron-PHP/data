<?php

namespace Neuron\Data\Setting\Source;

/**
 * environment variable based settings.
 */
class Env implements ISettingSource
{
	private \Neuron\Data\Env $_Env;

	public function __construct( \Neuron\Data\Env $Env )
	{
		$this->_Env = $Env;
	}

	/**
	 * This method is used to get the value of a setting stored in an environment variable.
	 * The section and name will be concatenated with an underscore and the value will be
	 * uppercased.
	 * e.g. get( 'test', 'name' ) will look for the environment variable TEST_NAME.
	 *
	 * @param string $SectionName
	 * @param string $Name
	 * @return string|null
	 */
	public function get( string $SectionName, string $Name ): ?string
	{
		$SectionName = strtoupper( $SectionName );
		$Name = strtoupper( $Name );
		return $this->_Env->get( "{$SectionName}_{$Name}" );
	}

	/**
	 * This method is used to set the value of a setting stored in an environment variable.
	 * The section and name will be concatenated with an underscore and the value will be
	 * uppercased.
	 * e.g. set( 'test', 'name', 'value' ) will set the environment variable TEST_NAME=value.
	 *
	 * @param string $SectionName
	 * @param string $Name
	 * @param string $Value
	 * @return ISettingSource
	 */

	public function set( string $SectionName, string $Name, string $Value ): ISettingSource
	{
		$SectionName = strtoupper( $SectionName );
		$Name = strtoupper( $Name );

		$this->_Env->put( "{$SectionName}_{$Name}=$Value" );

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
		foreach( $keys as $k )
		{
			if( !is_string( $k ) )
			{
				continue;
			}

			$kup = strtoupper( $k );
			$pos = strpos( $kup, '_' );
			if( $pos !== false && $pos > 0 )
			{
				$sec = substr( $kup, 0, $pos );
				$sections[$sec] = true;
			}
		}

		return array_values( array_keys( $sections ) );
	}

	/**
	 * This method is used to get the setting names for a section.
	 * We scan environment variable keys for those starting with SECTIONNAME_ and
	 * return the suffix names in lowercase (matching other sources' expectations).
	 *
	 * @param string $Section
	 * @return array
	 */

	public function getSectionSettingNames( string $Section ): array
	{
		$section = strtoupper( $Section );
		$keys = array_merge( array_keys( $_ENV ?? [] ), array_keys( $_SERVER ?? [] ) );

		$names = [];
		$prefix = $section . '_';
		$plen = strlen( $prefix );

		foreach( $keys as $k )
		{
			if( !is_string( $k ) )
			{
				continue;
			}

			$kup = strtoupper( $k );
			if( strncmp( $kup, $prefix, $plen ) === 0 )
			{
				$suffix = substr( $kup, $plen );
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
	 * @param string $SectionName
	 * @return array|null
	 */

	public function getSection( string $SectionName ): ?array
	{
		$section = strtoupper( $SectionName );
		$names = $this->getSectionSettingNames( $section );

		if( empty( $names ) )
		{
			return null;
		}

		$config = [];
		foreach( $names as $name )
		{
			$key = strtoupper( $section . '_' . $name );
			$value = $this->_Env->get( $key );
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
