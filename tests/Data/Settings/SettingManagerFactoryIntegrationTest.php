<?php

namespace Tests\Data\Settings;

use Neuron\Data\Settings\SettingManagerFactory;
use PHPUnit\Framework\TestCase;

class SettingManagerFactoryIntegrationTest extends TestCase
{
	private $configDir;

	protected function setUp(): void
	{
		parent::setUp();
		// Create temp directory for test configs
		$this->configDir = sys_get_temp_dir() . '/neuron_test_' . uniqid();
		mkdir( $this->configDir );
		mkdir( $this->configDir . '/environments' );
	}

	protected function tearDown(): void
	{
		// Clean up temp files
		if( is_dir( $this->configDir ) )
		{
			$this->removeDirectory( $this->configDir );
		}
		parent::tearDown();
	}

	private function removeDirectory( $dir )
	{
		if( !is_dir( $dir ) )
		{
			return;
		}
		$files = array_diff( scandir( $dir ), ['.', '..'] );
		foreach( $files as $file )
		{
			$path = $dir . '/' . $file;
			is_dir( $path ) ? $this->removeDirectory( $path ) : unlink( $path );
		}
		rmdir( $dir );
	}

	/**
	 * Test merging base config with environment config
	 */
	public function testMergeBaseWithEnvironmentConfig()
	{
		// Create base neuron.yaml
		$baseYaml = <<<YAML
database:
  use_secrets: true
  charset: utf8mb4

cache:
  enabled: true
  ttl: 3600

exceptions:
  passthrough:
    - 'Neuron\Cms\Exceptions\UnauthenticatedException'
YAML;
		file_put_contents( $this->configDir . '/neuron.yaml', $baseYaml );

		// Create production.yaml with overrides
		$prodYaml = <<<YAML
cache:
  enabled: false

exceptions:
  passthrough:
    - 'Neuron\Cms\Exceptions\UnauthenticatedException'
    - 'Neuron\Cms\Exceptions\EmailVerificationRequiredException'
    - 'Neuron\Cms\Exceptions\CsrfValidationException'
YAML;
		file_put_contents( $this->configDir . '/environments/production.yaml', $prodYaml );

		// Create SettingManager
		$manager = SettingManagerFactory::create( 'production', $this->configDir );

		// Test that base values are preserved
		$this->assertEquals( true, $manager->get( 'database', 'use_secrets' ) );
		$this->assertEquals( 'utf8mb4', $manager->get( 'database', 'charset' ) );
		$this->assertEquals( 3600, $manager->get( 'cache', 'ttl' ) );

		// Test that environment overrides work
		$this->assertEquals( false, $manager->get( 'cache', 'enabled' ) );

		// Test that exceptions array was replaced (not merged)
		$exceptions = $manager->getSection( 'exceptions' );
		$this->assertCount( 3, $exceptions['passthrough'] );
	}

	/**
	 * Test merging with secrets
	 */
	public function testMergeWithSecrets()
	{
		// Create base neuron.yaml
		$baseYaml = <<<YAML
database:
  use_secrets: true
YAML;
		file_put_contents( $this->configDir . '/neuron.yaml', $baseYaml );

		// For this test, we can't easily test actual encrypted secrets
		// but we can verify the structure exists
		$manager = SettingManagerFactory::create( 'test', $this->configDir );

		$this->assertEquals( true, $manager->get( 'database', 'use_secrets' ) );
	}

	/**
	 * Test environment variables as fallback
	 */
	public function testEnvironmentVariablesAsFallback()
	{
		// Create minimal base config
		$baseYaml = <<<YAML
site:
  name: 'My Site'
YAML;
		file_put_contents( $this->configDir . '/neuron.yaml', $baseYaml );

		// Set an environment variable
		putenv( 'SITE_URL=https://example.com' );

		$manager = SettingManagerFactory::create( 'test', $this->configDir );

		// Config value should be found
		$this->assertEquals( 'My Site', $manager->get( 'site', 'name' ) );

		// Environment variable should work as fallback
		$this->assertEquals( 'https://example.com', $manager->get( 'site', 'url' ) );

		// Cleanup
		putenv( 'SITE_URL' );
	}

	/**
	 * Test that merged config is single source
	 */
	public function testSingleSourceOfTruth()
	{
		$baseYaml = <<<YAML
database:
  charset: utf8mb4

logging:
  level: error
YAML;
		file_put_contents( $this->configDir . '/neuron.yaml', $baseYaml );

		$localYaml = <<<YAML
logging:
  level: debug
YAML;
		file_put_contents( $this->configDir . '/environments/local.yaml', $localYaml );

		$manager = SettingManagerFactory::create( 'local', $this->configDir );

		// Get entire database section - should work from single merged source
		$db = $manager->getSection( 'database' );
		$this->assertNotNull( $db );
		$this->assertEquals( 'utf8mb4', $db['charset'] );

		// Get entire logging section - should have merged values
		$logging = $manager->getSection( 'logging' );
		$this->assertNotNull( $logging );
		$this->assertEquals( 'debug', $logging['level'] );
	}
}
