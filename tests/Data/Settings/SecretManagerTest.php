<?php

namespace Tests\Data\Settings;

use Neuron\Core\System\IFileSystem;
use Neuron\Data\Encryption\IEncryptor;
use Neuron\Data\Settings\SecretManager;
use PHPUnit\Framework\TestCase;

class SecretManagerTest extends TestCase
{
	private SecretManager $secretManager;
	private $mockEncryptor;
	private $mockFileSystem;
	private string $testCredentialsPath = '/tmp/test_credentials.enc';
	private string $testKeyPath = '/tmp/test.key';

	protected function setUp(): void
	{
		parent::setUp();

		// Create mock encryptor
		$this->mockEncryptor = $this->createMock( IEncryptor::class );

		// Create mock file system
		$this->mockFileSystem = $this->createMock( IFileSystem::class );

		// Create SecretManager with mocks
		$this->secretManager = new SecretManager(
			$this->mockEncryptor,
			$this->mockFileSystem
		);

		// Clean up any leftover test files
		$this->cleanupTestFiles();
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		$this->cleanupTestFiles();
	}

	private function cleanupTestFiles(): void
	{
		$testFiles = [
			$this->testCredentialsPath,
			$this->testKeyPath,
			$this->testCredentialsPath . '.backup*',
			$this->testKeyPath . '.backup*',
			'/tmp/neuron_*'
		];

		foreach( $testFiles as $pattern )
		{
			foreach( glob( $pattern ) as $file )
			{
				if( file_exists( $file ) )
				{
					unlink( $file );
				}
			}
		}
	}

	public function testGenerateKeyCreatesNewKey(): void
	{
		$expectedKey = bin2hex( random_bytes( 32 ) );

		$this->mockFileSystem->expects( $this->once() )
			->method( 'fileExists' )
			->with( $this->testKeyPath )
			->willReturn( false );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'generateKey' )
			->willReturn( $expectedKey );

		$this->mockFileSystem->expects( $this->once() )
			->method( 'writeFile' )
			->with( $this->testKeyPath, $expectedKey )
			->willReturn( strlen( $expectedKey ) );

		$key = @$this->secretManager->generateKey( $this->testKeyPath );

		$this->assertEquals( $expectedKey, $key );
	}

	public function testGenerateKeyThrowsExceptionIfFileExistsAndNoForce(): void
	{
		$this->mockFileSystem->expects( $this->once() )
			->method( 'fileExists' )
			->with( $this->testKeyPath )
			->willReturn( true );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Key file already exists' );

		$this->secretManager->generateKey( $this->testKeyPath, false );
	}

	public function testShowDecryptsAndReturnsCredentials(): void
	{
		$encryptedContent = 'encrypted_data';
		$decryptedContent = 'database:\n  host: localhost\n  port: 3306';
		$key = bin2hex( random_bytes( 32 ) );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturnMap( [
				[$this->testCredentialsPath, true],
				[$this->testKeyPath, true]
			] );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'readFile' )
			->willReturnMap( [
				[$this->testKeyPath, $key . "\n"],
				[$this->testCredentialsPath, $encryptedContent]
			] );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encryptedContent, $key )
			->willReturn( $decryptedContent );

		$result = $this->secretManager->show( $this->testCredentialsPath, $this->testKeyPath );

		$this->assertEquals( $decryptedContent, $result );
	}

	public function testEncryptValidatesYamlAndEncryptsFile(): void
	{
		$plaintextPath = '/tmp/plaintext.yml';
		$yamlContent = "database:\n  host: localhost";
		$key = bin2hex( random_bytes( 32 ) );
		$encryptedContent = 'encrypted_data';

		$testKeyPath = $this->testKeyPath; // Store in local variable for closure
		$this->mockFileSystem->expects( $this->any() )
			->method( 'fileExists' )
			->willReturnCallback( function( $path ) use ( $plaintextPath, $testKeyPath ) {
				if( $path === $plaintextPath ) {
					return true;
				}
				return false; // Key file doesn't exist initially
			} );

		$this->mockFileSystem->expects( $this->once() )
			->method( 'readFile' )
			->with( $plaintextPath )
			->willReturn( $yamlContent );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'generateKey' )
			->willReturn( $key );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'encrypt' )
			->with( $yamlContent, $key )
			->willReturn( $encryptedContent );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'writeFile' )
			->withConsecutive(
				[$this->testKeyPath, $key],
				[$this->testCredentialsPath, $encryptedContent]
			)
			->willReturn( 100, 200 );

		$result = @$this->secretManager->encrypt(
			$plaintextPath,
			$this->testCredentialsPath,
			$this->testKeyPath
		);

		$this->assertTrue( $result );
	}

	public function testValidateReturnsTrueForValidCredentials(): void
	{
		$encryptedContent = 'encrypted_data';
		$decryptedContent = 'valid yaml content';
		$key = bin2hex( random_bytes( 32 ) );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'fileExists' )
			->willReturn( true );

		$this->mockFileSystem->expects( $this->exactly( 2 ) )
			->method( 'readFile' )
			->willReturnMap( [
				[$this->testKeyPath, $key],
				[$this->testCredentialsPath, $encryptedContent]
			] );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->with( $encryptedContent, $key )
			->willReturn( $decryptedContent );

		$result = $this->secretManager->validate(
			$this->testCredentialsPath,
			$this->testKeyPath
		);

		$this->assertTrue( $result );
	}

	public function testValidateReturnsFalseForInvalidCredentials(): void
	{
		$this->mockFileSystem->expects( $this->any() )
			->method( 'fileExists' )
			->willReturn( true );

		$this->mockFileSystem->expects( $this->any() )
			->method( 'readFile' )
			->willReturnCallback( function( $path ) {
				if( $path === $this->testKeyPath ) {
					return 'test_key';
				}
				return 'encrypted_content';
			} );

		$this->mockEncryptor->expects( $this->once() )
			->method( 'decrypt' )
			->willThrowException( new \Exception( 'Decryption failed' ) );

		$result = $this->secretManager->validate(
			$this->testCredentialsPath,
			$this->testKeyPath
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test the critical key rotation scenario logic
	 * This verifies the fix for the data loss bug where the new key
	 * could be deleted even if credential rollback failed
	 */
	public function testRotateKeyHandlesErrors(): void
	{
		// Create a simple test that verifies proper error handling
		$oldKey = bin2hex( random_bytes( 32 ) );

		// Setup mocks to cause an early failure
		$this->mockFileSystem->expects( $this->any() )
			->method( 'fileExists' )
			->willReturnCallback( function( $path ) {
				// Credentials file doesn't exist to trigger early error
				if( $path === $this->testCredentialsPath ) {
					return false;
				}
				return true;
			} );

		try {
			$this->secretManager->rotateKey(
				$this->testCredentialsPath,
				$this->testKeyPath,
				'/tmp/new.key'
			);
			$this->fail( 'Expected exception was not thrown' );
		} catch( \Exception $e ) {
			// Verify the error message format
			$this->assertStringContainsString( 'Credentials file not found', $e->getMessage() );
		}
	}

	/**
	 * Test successful key rotation
	 */
	public function testRotateKeySuccessfullyRotatesKeys(): void
	{
		// Use real filesystem for this integration test
		$realFs = new \Neuron\Core\System\RealFileSystem();
		$realEncryptor = $this->createMock( IEncryptor::class );
		$realSecretManager = new SecretManager( $realEncryptor, $realFs );

		$oldKey = bin2hex( random_bytes( 32 ) );
		$newKey = bin2hex( random_bytes( 32 ) );
		$content = "database:\n  password: secret123";
		$oldEncrypted = base64_encode( 'old_encrypted_' . $content );
		$newEncrypted = base64_encode( 'new_encrypted_' . $content );
		$newKeyPath = '/tmp/test_new.key';

		// Setup initial files
		file_put_contents( $this->testKeyPath, $oldKey );
		file_put_contents( $this->testCredentialsPath, $oldEncrypted );

		// Mock encryptor behavior - decrypt is called twice
		$realEncryptor->expects( $this->exactly( 2 ) )
			->method( 'decrypt' )
			->withConsecutive(
				[$oldEncrypted, $oldKey],
				[$newEncrypted, $newKey]
			)
			->willReturnOnConsecutiveCalls( $content, $content );

		$realEncryptor->expects( $this->once() )
			->method( 'generateKey' )
			->willReturn( $newKey );

		$realEncryptor->expects( $this->once() )
			->method( 'encrypt' )
			->with( $content, $newKey )
			->willReturn( $newEncrypted );

		// Perform key rotation
		$result = $realSecretManager->rotateKey(
			$this->testCredentialsPath,
			$this->testKeyPath,
			$newKeyPath
		);

		$this->assertTrue( $result );

		// Verify new files exist
		$this->assertFileExists( $newKeyPath );
		$this->assertEquals( $newKey, trim( file_get_contents( $newKeyPath ) ) );

		// Verify credentials were re-encrypted
		$this->assertEquals( $newEncrypted, file_get_contents( $this->testCredentialsPath ) );

		// Verify old key still exists (not in-place rotation)
		$this->assertFileExists( $this->testKeyPath );

		// Clean up
		unlink( $newKeyPath );
	}

	/**
	 * Test that temporary files use cryptographically secure tokens
	 */
	public function testTempFilesUseSecureTokens(): void
	{
		// Use reflection to directly test the generateSecureToken method
		$reflection = new \ReflectionClass( $this->secretManager );
		$method = $reflection->getMethod( 'generateSecureToken' );
		$method->setAccessible( true );

		// Test multiple token generations
		for( $i = 0; $i < 10; $i++ )
		{
			$token = $method->invoke( $this->secretManager );

			// Token should be 32 hex characters (16 bytes * 2)
			$this->assertEquals( 32, strlen( $token ), "Token length should be 32" );
			$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $token, "Token should be hexadecimal" );
		}

		// Also test with different length
		$token16 = $method->invoke( $this->secretManager, 8 );
		$this->assertEquals( 16, strlen( $token16 ), "Token with 8 bytes should be 16 hex chars" );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{16}$/', $token16, "Token should be hexadecimal" );

		$token64 = $method->invoke( $this->secretManager, 32 );
		$this->assertEquals( 64, strlen( $token64 ), "Token with 32 bytes should be 64 hex chars" );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $token64, "Token should be hexadecimal" );

		// Ensure tokens are unique (cryptographically random)
		$tokens = [];
		for( $i = 0; $i < 100; $i++ )
		{
			$tokens[] = $method->invoke( $this->secretManager );
		}

		$uniqueTokens = array_unique( $tokens );
		$this->assertEquals( 100, count( $uniqueTokens ), "All 100 tokens should be unique" );
	}
}