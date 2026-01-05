<?php

namespace Neuron\Data\Settings\Source;

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
	 * Supports automatic array parsing:
	 * - JSON format: '["value1","value2"]' returns array
	 * - Comma-separated: 'value1,value2,value3' returns array
	 * - Plain string: 'value' returns string (backward compatible)
	 *
	 * @param string $sectionName
	 * @param string $name
	 * @return mixed
	 */
	public function get( string $sectionName, string $name ): mixed
	{
		$sectionName = strtoupper( $sectionName );
		$name = strtoupper( $name );
		$value = $this->env->get( "{$sectionName}_{$name}" );

		if( $value === null )
		{
			return null;
		}

		return $this->parseValue( $value );
	}

	/**
	 * Parse environment variable value, auto-detecting arrays and deserializing special types.
	 *
	 * @param string $value
	 * @return mixed
	 */
	private function parseValue( string $value ): mixed
	{
		// Trim the value
		$value = trim( $value );

		// Handle null (empty string is how we serialize null)
		if( $value === '' )
		{
			return null;
		}

		// Handle booleans (must check before JSON parsing)
		if( $value === 'true' )
		{
			return true;
		}
		if( $value === 'false' )
		{
			return false;
		}

		// Try JSON parsing if value starts with [ or {
		if( in_array( $value[0], [ '[', '{' ] ) )
		{
			$decoded = json_decode( $value, true );
			if( json_last_error() === JSON_ERROR_NONE )
			{
				return $decoded;
			}
			// If JSON parsing fails, fall through to return as string
		}

		// Try comma-separated parsing
		if( strpos( $value, ',' ) !== false )
		{
			$parts = explode( ',', $value );
			return array_map( 'trim', $parts );
		}

		// Return as-is (plain string)
		return $value;
	}

	/**
	 * This method is used to set the value of a setting stored in an environment variable.
	 * The section and name will be concatenated with an underscore and the value will be
	 * uppercased.
	 * e.g. set( 'test', 'name', 'value' ) will set the environment variable TEST_NAME=value.
	 *
	 * Non-scalar values (arrays, objects) are automatically serialized to JSON format
	 * to prevent data corruption. The get() method will automatically deserialize them.
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

		// Serialize non-scalar values to JSON to prevent data corruption
		if( is_array( $value ) || is_object( $value ) )
		{
			$serializedValue = json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if( json_last_error() !== JSON_ERROR_NONE )
			{
				throw new \RuntimeException(
					"Cannot serialize value for environment variable {$sectionName}_{$name}: " .
					json_last_error_msg()
				);
			}
		}
		elseif( is_bool( $value ) )
		{
			// Convert booleans to string representation
			$serializedValue = $value ? 'true' : 'false';
		}
		elseif( is_null( $value ) )
		{
			// Convert null to empty string
			$serializedValue = '';
		}
		else
		{
			// Scalar values (strings, integers, floats) are used as-is
			$serializedValue = (string) $value;
		}

		$this->env->put( "{$sectionName}_{$name}={$serializedValue}" );

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
	 * Values are automatically parsed (arrays from JSON/CSV are returned as arrays).
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
			// Use the class's get() method to leverage value parsing
			$value = $this->get( $sectionName, $name );
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
