<?php

namespace Tests\Data\Settings\Source;

use Neuron\Core\System\IFileSystem;
use Neuron\Data\Encryption\IEncryptor;
use Neuron\Data\Settings\Source\Encrypted;
use PHPUnit\Framework\TestCase;

class EncryptedTest extends TestCase
{
	private $mockFileSystem;
	private $mockEncryptor;
	private string $testCredentialsPath = '/tmp/test_creds.enc';
	private string $testKeyPath = '/tmp/test.key';

	protected function setUp(): void
	{
		parent::setUp();

		$this->mockFileSystem = $this->createMock( IFileSystem::class );
		$this->mockEncryptor = $this->createMock( IEncryptor::class );

		// Clear any test environment variables
		putenv( 'NEURON_TEST_KEY' );
		putenv( 'NEURON_MASTER_KEY' );
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		// Clean up environment
		putenv( 'NEURON_TEST_KEY' );
		putenv( 'NEURON_MASTER_KEY' );
	}

	/**
	 * Test that key is read from file when it exists
	 */
	public function testKeyReadFromFile(): void
	{
		$keyContent = 'test_encryption_key_12345678901234567890123456789012';
		$encryptedData = 'encrypted_yaml_content';
		$decryptedYaml = "database:\n  host: localhost\n  port: 3306";

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, true]
			] );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'readFile' )
			->willReturnMap( [
				[$this->testKeyPath, $keyContent],
				[$this->testCredentialsPath, $encryptedData]
			] );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encryptedData, trim( $keyContent ) )
			->willReturn( $decryptedYaml );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		$value = $source->get( 'database', 'host' );
		$this->assertEquals( 'localhost', $value );
	}

	/**
	 * Test that key falls back to environment variable when file doesn't exist
	 * This tests the getenv() change we made
	 */
	public function testKeyFallbackToEnvironmentVariable(): void
	{
		$keyFromEnv = 'key_from_environment_variable_1234567890123456789012';
		$encryptedData = 'encrypted_content';
		$decryptedYaml = "api:\n  key: secret_key\n  url: https://api.example.com";

		// Set environment variable
		putenv( 'NEURON_TEST_KEY=' . $keyFromEnv );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, false] // Key file doesn't exist
			] );

		$this->mockFileSystem->expects( $this->once() )
			->method( 'readFile' )
			->with( $this->testCredentialsPath )
			->willReturn( $encryptedData );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encryptedData, $keyFromEnv )
			->willReturn( $decryptedYaml );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		$value = $source->get( 'api', 'key' );
		$this->assertEquals( 'secret_key', $value );
	}

	/**
	 * Test that getenv() is used instead of $_ENV
	 * This ensures compatibility with different PHP configurations
	 */
	public function testUsesGetenvNotEnvSuperglobal(): void
	{
		$keyFromEnv = 'key_from_getenv_only_1234567890123456789012345678';
		$encryptedData = 'encrypted';
		$decryptedYaml = "test:\n  value: success";

		// Clear $_ENV if it exists
		if( isset( $_ENV['NEURON_TEST_KEY'] ) )
		{
			unset( $_ENV['NEURON_TEST_KEY'] );
		}

		// Set via putenv (which getenv can read but $_ENV might not)
		putenv( 'NEURON_TEST_KEY=' . $keyFromEnv );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, false]
			] );

		$this->mockFileSystem->expects( $this->once() )
			->method( 'readFile' )
			->with( $this->testCredentialsPath )
			->willReturn( $encryptedData );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encryptedData, $keyFromEnv )
			->willReturn( $decryptedYaml );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		$value = $source->get( 'test', 'value' );
		$this->assertEquals( 'success', $value );
	}

	/**
	 * Test that empty environment variable is treated as no key
	 */
	public function testEmptyEnvironmentVariableIsRejected(): void
	{
		// Set environment variable to empty string
		putenv( 'NEURON_TEST_KEY=' );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, false] // Key file doesn't exist
			] );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		// Should return null for any setting when key is empty
		$this->assertNull( $source->get( 'any', 'setting' ) );
		$this->assertEmpty( $source->getSectionNames() );

		// Clean up
		putenv( 'NEURON_TEST_KEY' );
	}

	/**
	 * Test that empty RAILS_MASTER_KEY is also rejected
	 */
	public function testEmptyRailsMasterKeyIsRejected(): void
	{
		// Set RAILS_MASTER_KEY to empty string
		putenv( 'RAILS_MASTER_KEY=' );

		$masterKeyPath = '/some/path/master.key';

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$masterKeyPath, false] // Key file doesn't exist
			] );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$masterKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		// Should return null for any setting when key is empty
		$this->assertNull( $source->get( 'any', 'setting' ) );
		$this->assertEmpty( $source->getSectionNames() );

		// Clean up
		putenv( 'RAILS_MASTER_KEY' );
	}

	/**
	 * Test that missing key results in empty settings (no exception)
	 */
	public function testMissingKeyResultsInEmptySettings(): void
	{
		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, false]
			] );

		// No environment variable set

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		// Should return null for any setting when key is missing
		$this->assertNull( $source->get( 'any', 'setting' ) );
		$this->assertEmpty( $source->getSectionNames() );
	}

	/**
	 * Test that missing credentials file results in empty settings (no exception)
	 */
	public function testMissingCredentialsFileResultsInEmptySettings(): void
	{
		$this->mockFileSystem->expects( $this->once() )
			->method( 'fileExists' )
			->with( $this->testCredentialsPath )
			->willReturn( false );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		// Should return null for any setting when file is missing
		$this->assertNull( $source->get( 'any', 'setting' ) );
		$this->assertEmpty( $source->getSectionNames() );
	}

	/**
	 * Test get returns correct value from nested configuration
	 */
	public function testGetReturnsCorrectValue(): void
	{
		$key = 'test_key';
		$encrypted = 'encrypted_data';
		$decrypted = "database:\n  host: db.example.com\n  port: 5432\n  credentials:\n    username: dbuser\n    password: dbpass";

		$this->setupMocksForSuccessfulDecryption( $key, $encrypted, $decrypted );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		$this->assertEquals( 'db.example.com', $source->get( 'database', 'host' ) );
		$this->assertEquals( 5432, $source->get( 'database', 'port' ) );
		// Nested sections need to be accessed directly by section name
		$this->assertNull( $source->get( 'database.credentials', 'username' ) );
		$this->assertNull( $source->get( 'database.credentials', 'password' ) );
	}

	/**
	 * Test get returns null for non-existent keys
	 */
	public function testGetReturnsNullForNonExistentKey(): void
	{
		$key = 'test_key';
		$encrypted = 'encrypted_data';
		$decrypted = "existing:\n  key: value";

		$this->setupMocksForSuccessfulDecryption( $key, $encrypted, $decrypted );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		$this->assertNull( $source->get( 'nonexistent', 'key' ) );
	}

	/**
	 * Test getSectionNames returns correct sections
	 */
	public function testGetSectionNames(): void
	{
		$key = 'test_key';
		$encrypted = 'encrypted_data';
		$decrypted = "database:\n  host: localhost\napi:\n  key: secret\ncache:\n  driver: redis";

		$this->setupMocksForSuccessfulDecryption( $key, $encrypted, $decrypted );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		$sections = $source->getSectionNames();
		$this->assertCount( 3, $sections );
		$this->assertContains( 'database', $sections );
		$this->assertContains( 'api', $sections );
		$this->assertContains( 'cache', $sections );
	}

	/**
	 * Test getSection returns entire section
	 */
	public function testGetSectionReturnsEntireSection(): void
	{
		$key = 'test_key';
		$encrypted = 'encrypted_data';
		$decrypted = "database:\n  host: localhost\n  port: 3306\n  name: mydb";

		$this->setupMocksForSuccessfulDecryption( $key, $encrypted, $decrypted );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		$section = $source->getSection( 'database' );
		$this->assertIsArray( $section );
		$this->assertEquals( [
			'host' => 'localhost',
			'port' => 3306,
			'name' => 'mydb'
		], $section );
	}

	/**
	 * Test that master key environment variable is checked
	 */
	public function testMasterKeyEnvironmentVariable(): void
	{
		$masterKey = 'master_key_from_env_12345678901234567890123456789';
		$encrypted = 'encrypted';
		$decrypted = "secret:\n  value: from_master_key";

		putenv( 'NEURON_MASTER_KEY=' . $masterKey );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				['/some/path/master.key', false]
			] );

		$this->mockFileSystem->expects( $this->once() )
			->method( 'readFile' )
			->with( $this->testCredentialsPath )
			->willReturn( $encrypted );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encrypted, $masterKey )
			->willReturn( $decrypted );

		$source = new Encrypted(
			$this->testCredentialsPath,
			'/some/path/master.key',
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		$value = $source->get( 'secret', 'value' );
		$this->assertEquals( 'from_master_key', $value );
	}

	/**
	 * Test that scalar YAML content is handled correctly
	 */
	public function testScalarYamlContentIsHandled(): void
	{
		$key = 'test_key';
		$encrypted = 'encrypted_data';
		$scalarYaml = 'just a string value'; // This will parse as a scalar, not an array

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, true]
			] );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'readFile' )
			->willReturnMap( [
				[$this->testKeyPath, $key],
				[$this->testCredentialsPath, $encrypted]
			] );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encrypted, trim( $key ) )
			->willReturn( $scalarYaml );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		// getSectionNames should not crash with TypeError
		$sections = $source->getSectionNames();
		$this->assertIsArray( $sections );
		$this->assertContains( 'value', $sections ); // Scalar gets wrapped in 'value' key

		// The scalar value is stored in a 'value' section as an array
		$section = $source->getSection( 'value' );
		$this->assertIsArray( $section );
		$this->assertArrayHasKey( 'data', $section );
		$this->assertEquals( 'just a string value', $section['data'] );

		// Can also access via get()
		$value = $source->get( 'value', 'data' );
		$this->assertEquals( 'just a string value', $value );
	}

	/**
	 * Test that numeric YAML content is handled correctly
	 */
	public function testNumericYamlContentIsHandled(): void
	{
		$key = 'test_key';
		$encrypted = 'encrypted_data';
		$numericYaml = '42'; // This will parse as an integer

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, true]
			] );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'readFile' )
			->willReturnMap( [
				[$this->testKeyPath, $key],
				[$this->testCredentialsPath, $encrypted]
			] );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encrypted, trim( $key ) )
			->willReturn( $numericYaml );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		// Should not crash
		$sections = $source->getSectionNames();
		$this->assertIsArray( $sections );
		$this->assertContains( 'value', $sections );

		// The numeric value is stored in a 'value' section as an array
		$section = $source->getSection( 'value' );
		$this->assertIsArray( $section );
		$this->assertArrayHasKey( 'data', $section );
		$this->assertEquals( 42, $section['data'] );

		// Can also access via get()
		$value = $source->get( 'value', 'data' );
		$this->assertEquals( 42, $value );
	}

	/**
	 * Test that getSection returns null for scalar section values (type safety)
	 */
	public function testGetSectionReturnsNullForScalarSectionValue(): void
	{
		$key = 'test_key';
		$encrypted = 'encrypted_data';
		// YAML with a section that has a scalar value directly
		$malformedYaml = "database:\n  host: localhost\napi_key: just_a_string_not_an_object";

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, true]
			] );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'readFile' )
			->willReturnMap( [
				[$this->testKeyPath, $key],
				[$this->testCredentialsPath, $encrypted]
			] );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encrypted, trim( $key ) )
			->willReturn( $malformedYaml );

		$source = new Encrypted(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		// database section should work normally
		$dbSection = $source->getSection( 'database' );
		$this->assertIsArray( $dbSection );
		$this->assertArrayHasKey( 'host', $dbSection );

		// api_key section has a scalar value, getSection should return null (not the string)
		$apiSection = $source->getSection( 'api_key' );
		$this->assertNull( $apiSection, 'getSection should return null for scalar section values' );
	}

	/**
	 * Helper method to setup mocks for successful decryption
	 */
	private function setupMocksForSuccessfulDecryption( string $key, string $encrypted, string $decrypted ): void
	{
		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, true]
			] );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'readFile' )
			->willReturnMap( [
				[$this->testKeyPath, $key],
				[$this->testCredentialsPath, $encrypted]
			] );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encrypted, trim( $key ) )
			->willReturn( $decrypted );
	}
}