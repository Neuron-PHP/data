<?php

namespace Neuron\Data\Setting\Source;

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

	public function set( string $SectionName, string $Name, string $Value ): ISettingSource
	{
		$SectionName = strtoupper( $SectionName );
		$Name = strtoupper( $Name );

		$this->_Env->put( "{$SectionName}_{$Name}=$Value" );

		return $this;
	}

	public function getSectionNames(): array
	{
		return [];
	}

	public function getSectionSettingNames( string $Section ): array
	{
		return [];
	}

	public function save(): bool
	{
		return false;
	}
}
