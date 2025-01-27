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
	 * This is not possible so this method will always return an empty array.
	 *
	 * @return array
	 */

	public function getSectionNames(): array
	{
		return [];
	}

	/**
	 * This method is used to get the setting names for a section.
	 * This is not possible so this method will always return an empty array.
	 *
	 * @param string $Section
	 * @return array
	 */

	public function getSectionSettingNames( string $Section ): array
	{
		return [];
	}

	/**
	 * This method is used to save the settings to the environment variables.
	 * This is not possible so this method will always return false.
	 *
	 * @return bool
	 */

	public function save(): bool
	{
		return false;
	}
}
