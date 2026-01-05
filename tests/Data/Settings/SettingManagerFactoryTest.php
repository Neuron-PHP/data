<?php

namespace Tests\Data\Settings;

use Neuron\Data\Env;
use Neuron\Data\Settings\SettingManager;
use Neuron\Data\Settings\SettingManagerFactory;
use PHPUnit\Framework\TestCase;

class SettingManagerFactoryTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		// Reset Env singleton
		Env::getInstance()->reset();

		// Clean up test environment
		putenv( 'APP_ENV' );
		putenv( 'TEST_SETTING' );
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		// Clean up
		putenv( 'APP_ENV' );
		putenv( 'TEST_SETTING' );
		Env::getInstance()->reset();
	}

	/**
	 * Test that create() returns a SettingManager instance
	 */
	public function testCreateReturnsSettingManager(): void
	{
		$manager = SettingManagerFactory::create();

		$this->assertInstanceOf( SettingManager::class, $manager );
	}

	/**
	 * Test that create() properly handles Env singleton
	 * This tests the fix where we changed from 'new Env()' to 'Env::getInstance()'
	 */
	public function testCreateHandlesEnvSingletonCorrectly(): void
	{
		// This should not throw an error about private constructor
		$manager = SettingManagerFactory::create();

		// Set an environment variable
		putenv( 'TEST_SETTING=from_env' );

		// The manager should be able to read it through the Env source
		$value = $manager->get( 'test', 'setting' );

		$this->assertEquals( 'from_env', $value );
	}

	/**
	 * Test create with specific environment
	 */
	public function testCreateWithSpecificEnvironment(): void
	{
		$manager = SettingManagerFactory::create( 'production' );

		$this->assertInstanceOf( SettingManager::class, $manager );
	}

	/**
	 * Test create with custom config path
	 */
	public function testCreateWithCustomConfigPath(): void
	{
		$customPath = '/tmp/test_config_' . uniqid();
		mkdir( $customPath );

		try {
			// Create a test config file
			file_put_contents(
				$customPath . '/application.yaml',
				"test:\n  value: from_yaml"
			);

			$manager = SettingManagerFactory::create( null, $customPath );

			$value = $manager->get( 'test', 'value' );
			$this->assertEquals( 'from_yaml', $value );
		}
		finally {
			// Clean up
			@unlink( $customPath . '/application.yaml' );
			@rmdir( $customPath );
		}
	}

	/**
	 * Test that environment variables have highest priority
	 */
	public function testEnvironmentVariablePriority(): void
	{
		$configPath = '/tmp/test_config_' . uniqid();
		mkdir( $configPath );

		try {
			// Create config file
			file_put_contents(
				$configPath . '/application.yaml',
				"test:\n  priority: from_yaml"
			);

			// Set environment variable (should override yaml)
			putenv( 'TEST_PRIORITY=from_env' );

			$manager = SettingManagerFactory::create( null, $configPath );

			$value = $manager->get( 'test', 'priority' );
			$this->assertEquals( 'from_env', $value );
		}
		finally {
			// Clean up
			@unlink( $configPath . '/application.yaml' );
			@rmdir( $configPath );
			putenv( 'TEST_PRIORITY' );
		}
	}

	/**
	 * Test createCustom with various source types
	 */
	public function testCreateCustomWithVariousSources(): void
	{
		$configPath = '/tmp/test_config_' . uniqid();
		mkdir( $configPath );

		try {
			// Create a yaml file
			file_put_contents(
				$configPath . '/test.yaml',
				"test:\n  yaml_value: from_yaml"
			);

			$sources = [
				[
					'type' => 'yaml',
					'path' => $configPath . '/test.yaml',
					'name' => 'yaml_source'
				],
				[
					'type' => 'env',
					'name' => 'env_source'
				]
			];

			$manager = SettingManagerFactory::createCustom( $sources );

			$this->assertInstanceOf( SettingManager::class, $manager );

			// Test yaml source
			$value = $manager->get( 'test', 'yaml_value' );
			$this->assertEquals( 'from_yaml', $value );

			// Test env source
			putenv( 'TEST_ENV_VALUE=from_env' );
			$value = $manager->get( 'test', 'env_value' );
			$this->assertEquals( 'from_env', $value );
		}
		finally {
			// Clean up
			@unlink( $configPath . '/test.yaml' );
			@rmdir( $configPath );
			putenv( 'TEST_ENV_VALUE' );
		}
	}

	/**
	 * Test createForTesting with in-memory configuration
	 */
	public function testCreateForTesting(): void
	{
		$config = [
			'test' => [
				'key1' => 'value1',
				'key2' => 'value2'
			],
			'another' => [
				'setting' => 'value'
			]
		];

		$manager = SettingManagerFactory::createForTesting( $config );

		// Memory source needs flattened keys
		$this->assertInstanceOf( SettingManager::class, $manager );
	}

	/**
	 * Test getExpectedStructure returns correct paths
	 */
	public function testGetExpectedStructure(): void
	{
		putenv( 'APP_ENV=test' );

		$structure = SettingManagerFactory::getExpectedStructure();

		$this->assertArrayHasKey( 'base_config', $structure );
		$this->assertArrayHasKey( 'environment_config', $structure );
		$this->assertArrayHasKey( 'base_secrets', $structure );
		$this->assertArrayHasKey( 'master_key', $structure );
		$this->assertArrayHasKey( 'environment_secrets', $structure );
		$this->assertArrayHasKey( 'environment_key', $structure );

		$this->assertEquals( 'config/application.yaml', $structure['base_config'] );
		$this->assertEquals( 'config/environments/test.yaml', $structure['environment_config'] );
		$this->assertEquals( 'config/secrets.yml.enc', $structure['base_secrets'] );
		$this->assertEquals( 'config/master.key', $structure['master_key'] );
		$this->assertEquals( 'config/secrets/test.yml.enc', $structure['environment_secrets'] );
		$this->assertEquals( 'config/secrets/test.key', $structure['environment_key'] );
	}

	/**
	 * Test getExpectedStructure with custom base path
	 */
	public function testGetExpectedStructureWithCustomPath(): void
	{
		putenv( 'APP_ENV=production' );

		$structure = SettingManagerFactory::getExpectedStructure( '/custom/config' );

		$this->assertEquals( '/custom/config/application.yaml', $structure['base_config'] );
		$this->assertEquals( '/custom/config/environments/production.yaml', $structure['environment_config'] );
	}

	/**
	 * Test that missing files are gracefully handled
	 */
	public function testMissingFilesAreGracefullyHandled(): void
	{
		// Create with non-existent config path
		$manager = SettingManagerFactory::create( null, '/non/existent/path' );

		$this->assertInstanceOf( SettingManager::class, $manager );

		// Should still work with environment variables
		putenv( 'TEST_FALLBACK=env_value' );
		$value = $manager->get( 'test', 'fallback' );
		$this->assertEquals( 'env_value', $value );
	}

	/**
	 * Test that encrypted sources are skipped if decryption fails
	 */
	public function testEncryptedSourcesSkippedOnFailure(): void
	{
		$configPath = '/tmp/test_config_' . uniqid();
		mkdir( $configPath );

		try {
			// Create an invalid encrypted file
			file_put_contents(
				$configPath . '/secrets.yml.enc',
				'invalid encrypted content'
			);

			// This should not throw, encrypted source should be skipped
			$manager = SettingManagerFactory::create( null, $configPath );

			$this->assertInstanceOf( SettingManager::class, $manager );
		}
		finally {
			// Clean up
			@unlink( $configPath . '/secrets.yml.enc' );
			@rmdir( $configPath );
		}
	}
}