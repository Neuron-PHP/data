<?php

namespace Tests\Data\Settings;

use Neuron\Data\Settings\SettingManager;
use PHPUnit\Framework\TestCase;

class DeepMergeTest extends TestCase
{
	/**
	 * Test deep merge with nested arrays
	 */
	public function testDeepMergeNestedArrays()
	{
		$base = [
			'database' => [
				'use_secrets' => true,
				'charset' => 'utf8mb4'
			],
			'cache' => [
				'enabled' => true
			]
		];

		$override = [
			'database' => [
				'adapter' => 'pgsql',
				'host' => 'localhost',
				'port' => 5432
			]
		];

		$result = SettingManager::deepMerge( $base, $override );

		// Database section should be merged, not replaced
		$this->assertEquals( true, $result['database']['use_secrets'] );
		$this->assertEquals( 'utf8mb4', $result['database']['charset'] );
		$this->assertEquals( 'pgsql', $result['database']['adapter'] );
		$this->assertEquals( 'localhost', $result['database']['host'] );
		$this->assertEquals( 5432, $result['database']['port'] );

		// Cache should still exist
		$this->assertEquals( true, $result['cache']['enabled'] );
	}

	/**
	 * Test deep merge with exceptions passthrough array
	 */
	public function testDeepMergeExceptionsPassthrough()
	{
		$base = [
			'exceptions' => [
				'passthrough' => [
					'Neuron\Cms\Exceptions\UnauthenticatedException'
				]
			]
		];

		$override = [
			'exceptions' => [
				'passthrough' => [
					'Neuron\Cms\Exceptions\EmailVerificationRequiredException',
					'Neuron\Cms\Exceptions\CsrfValidationException'
				]
			]
		];

		$result = SettingManager::deepMerge( $base, $override );

		// Arrays should be replaced, not merged
		$this->assertCount( 2, $result['exceptions']['passthrough'] );
		$this->assertEquals( 'Neuron\Cms\Exceptions\EmailVerificationRequiredException', $result['exceptions']['passthrough'][0] );
		$this->assertEquals( 'Neuron\Cms\Exceptions\CsrfValidationException', $result['exceptions']['passthrough'][1] );
	}

	/**
	 * Test deep merge with scalar override
	 */
	public function testDeepMergeScalarOverride()
	{
		$base = [
			'logging' => [
				'level' => 'error',
				'file' => 'storage/logs/app.log'
			]
		];

		$override = [
			'logging' => [
				'level' => 'debug'
			]
		];

		$result = SettingManager::deepMerge( $base, $override );

		$this->assertEquals( 'debug', $result['logging']['level'] );
		$this->assertEquals( 'storage/logs/app.log', $result['logging']['file'] );
	}

	/**
	 * Test deep merge with three levels deep
	 */
	public function testDeepMergeThreeLevelsDeep()
	{
		$base = [
			'auth' => [
				'session' => [
					'lifetime' => 120,
					'cookie_name' => 'neuron_session'
				],
				'passwords' => [
					'min_length' => 8
				]
			]
		];

		$override = [
			'auth' => [
				'session' => [
					'lifetime' => 60
				]
			]
		];

		$result = SettingManager::deepMerge( $base, $override );

		$this->assertEquals( 60, $result['auth']['session']['lifetime'] );
		$this->assertEquals( 'neuron_session', $result['auth']['session']['cookie_name'] );
		$this->assertEquals( 8, $result['auth']['passwords']['min_length'] );
	}

	/**
	 * Test deep merge adding new keys
	 */
	public function testDeepMergeAddingNewKeys()
	{
		$base = [
			'site' => [
				'name' => 'My Site'
			]
		];

		$override = [
			'site' => [
				'url' => 'https://example.com'
			],
			'theme' => [
				'admin' => 'vapor'
			]
		];

		$result = SettingManager::deepMerge( $base, $override );

		$this->assertEquals( 'My Site', $result['site']['name'] );
		$this->assertEquals( 'https://example.com', $result['site']['url'] );
		$this->assertEquals( 'vapor', $result['theme']['admin'] );
	}
}
