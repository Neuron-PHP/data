<?php

namespace Tests\Data\Settings;

use Neuron\Data\Settings\EnvironmentDetector;
use PHPUnit\Framework\TestCase;

class EnvironmentDetectorTest extends TestCase
{
	/**
	 * Save original environment values
	 */
	private $originalEnv = [];

	protected function setUp(): void
	{
		parent::setUp();
		// Save original environment values
		$this->originalEnv['APP_ENV'] = getenv( 'APP_ENV' );
		$this->originalEnv['NEURON_ENV'] = getenv( 'NEURON_ENV' );
		$this->originalEnv['ENVIRONMENT'] = getenv( 'ENVIRONMENT' );
		$this->originalEnv['APPLICATION_ENV'] = getenv( 'APPLICATION_ENV' );

		// Clear environment
		putenv( 'APP_ENV' );
		putenv( 'NEURON_ENV' );
		putenv( 'ENVIRONMENT' );
		putenv( 'APPLICATION_ENV' );
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		// Restore original environment values
		foreach( $this->originalEnv as $key => $value )
		{
			if( $value !== false )
			{
				putenv( "$key=$value" );
			}
			else
			{
				putenv( $key );
			}
		}
	}

	/**
	 * Test detection with APP_ENV variable
	 */
	public function testDetectWithAppEnv(): void
	{
		putenv( 'APP_ENV=production' );

		$env = EnvironmentDetector::detect();
		$this->assertEquals( 'production', $env );
	}

	/**
	 * Test detection with NEURON_ENV variable
	 */
	public function testDetectWithNeuronEnv(): void
	{
		putenv( 'NEURON_ENV=production' );

		$env = EnvironmentDetector::detect();
		$this->assertEquals( 'production', $env );
	}

	/**
	 * Test detection with ENVIRONMENT variable
	 */
	public function testDetectWithEnvironmentVar(): void
	{
		putenv( 'ENVIRONMENT=staging' );

		$env = EnvironmentDetector::detect();
		$this->assertEquals( 'staging', $env );
	}

	/**
	 * Test detection with APPLICATION_ENV variable
	 */
	public function testDetectWithApplicationEnv(): void
	{
		putenv( 'APPLICATION_ENV=testing' );

		$env = EnvironmentDetector::detect();
		// 'testing' is normalized to 'test'
		$this->assertEquals( 'test', $env );
	}

	/**
	 * Test priority order - APP_ENV takes precedence
	 */
	public function testPriorityOrder(): void
	{
		putenv( 'APP_ENV=production' );
		putenv( 'NEURON_ENV=staging' );
		putenv( 'ENVIRONMENT=testing' );
		putenv( 'APPLICATION_ENV=development' );

		$env = EnvironmentDetector::detect();
		$this->assertEquals( 'production', $env );

		// Test NEURON_ENV takes precedence when APP_ENV is not set
		putenv( 'APP_ENV' );
		$env = EnvironmentDetector::detect();
		$this->assertEquals( 'staging', $env );
	}

	/**
	 * Test default to development when no environment variables set
	 */
	public function testDefaultToDevelopment(): void
	{
		// All env vars already cleared in setUp
		$env = EnvironmentDetector::detect();
		$this->assertEquals( 'development', $env );
	}

	/**
	 * Test that CLI does not force development environment
	 * (testing the fix we made)
	 */
	public function testCliDoesNotForceDevelopment(): void
	{
		putenv( 'APP_ENV=production' );

		// Even though we're running from CLI (PHPUnit),
		// it should respect the APP_ENV setting
		$env = EnvironmentDetector::detect();
		$this->assertEquals( 'production', $env );
	}

	/**
	 * Test with empty string environment variable
	 */
	public function testEmptyEnvironmentVariable(): void
	{
		putenv( 'APP_ENV=' );

		$env = EnvironmentDetector::detect();
		// Empty string should be ignored, falling back to development
		$this->assertEquals( 'development', $env );
	}

	/**
	 * Test with whitespace-only environment variable
	 */
	public function testWhitespaceEnvironmentVariable(): void
	{
		putenv( 'APP_ENV=   ' );

		$env = EnvironmentDetector::detect();
		// Whitespace should be trimmed
		$this->assertEquals( 'development', $env );
	}

	/**
	 * Test common environment names and their normalization
	 */
	public function testCommonEnvironmentNames(): void
	{
		// Map of input => expected normalized output
		$environments = [
			'development' => 'development',
			'testing' => 'test',      // normalized to 'test'
			'staging' => 'staging',
			'production' => 'production',
			'local' => 'development', // alias for 'development'
			'dev' => 'development',   // alias for 'development'
			'test' => 'test',
			'prod' => 'production',   // alias for 'production'
			'qa' => null,             // not a valid environment, defaults to development
			'uat' => null             // not a valid environment, defaults to development
		];

		foreach( $environments as $envName => $expected )
		{
			putenv( "APP_ENV=$envName" );
			$env = EnvironmentDetector::detect();
			$expectedResult = $expected ?? 'development'; // Invalid ones default to development
			$this->assertEquals( $expectedResult, $env, "Failed for environment: $envName" );
			putenv( 'APP_ENV' ); // Clear after each test
		}
	}

	/**
	 * Test custom environment names default to development
	 */
	public function testCustomEnvironmentNamesDefaultToDevelopment(): void
	{
		putenv( 'APP_ENV=custom-env-name' );

		$env = EnvironmentDetector::detect();
		// Custom/invalid environment names default to development
		$this->assertEquals( 'development', $env );
	}

	/**
	 * Test environment name with special characters defaults to development
	 */
	public function testEnvironmentWithSpecialCharactersDefaultsToDevelopment(): void
	{
		putenv( 'APP_ENV=dev-feature-123' );

		$env = EnvironmentDetector::detect();
		// Invalid environment names default to development
		$this->assertEquals( 'development', $env );
	}

	/**
	 * Test that getenv is used (not $_ENV)
	 * This ensures compatibility with different PHP configurations
	 */
	public function testUsesGetenvNotEnvSuperglobal(): void
	{
		// Clear $_ENV if it exists
		if( isset( $_ENV['APP_ENV'] ) )
		{
			unset( $_ENV['APP_ENV'] );
		}

		// Set via putenv (which getenv can read)
		putenv( 'APP_ENV=production' );

		$env = EnvironmentDetector::detect();
		$this->assertEquals( 'production', $env );
	}
}