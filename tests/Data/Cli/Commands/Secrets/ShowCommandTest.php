<?php

namespace Tests\Data\Cli\Commands\Secrets;

use Neuron\Data\Cli\Commands\Secrets\ShowCommand;
use Neuron\Data\Settings\SecretManager;
use PHPUnit\Framework\TestCase;
use Neuron\Cli\Input\Input;
use Neuron\Cli\Output\Output;

class ShowCommandTest extends TestCase
{
	private string $testConfigPath;
	private ShowCommand $command;
	private SecretManager $secretManager;

	protected function setUp(): void
	{
		parent::setUp();

		// Skip these tests until CLI dependency is available
		if( !class_exists( 'Neuron\Cli\Input\Input' ) )
		{
			$this->markTestSkipped( 'CLI component not available' );
		}

		// Create a temporary directory for testing
		$this->testConfigPath = sys_get_temp_dir() . '/test_secrets_show_' . uniqid();
		mkdir( $this->testConfigPath, 0755, true );

		$this->command = new ShowCommand();
		$this->secretManager = new SecretManager();
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		// Clean up test files
		$this->removeDirectory( $this->testConfigPath );
	}

	/**
	 * Test that the command has the correct name
	 */
	public function testGetName(): void
	{
		$this->assertEquals( 'secrets:show', $this->command->getName() );
	}

	/**
	 * Test that the command has a description
	 */
	public function testGetDescription(): void
	{
		$this->assertEquals( 'Show decrypted secrets', $this->command->getDescription() );
	}

	/**
	 * Test showing base secrets
	 */
	public function testExecuteShowsBaseSecrets(): void
	{
		// Create test secrets
		$keyPath = $this->testConfigPath . '/master.key';
		$credentialsPath = $this->testConfigPath . '/secrets.yml.enc';

		$key = $this->secretManager->generateKey( $keyPath );

		$tempPlaintextPath = $this->testConfigPath . '/temp_plaintext.yml';
		$testData = "database:\n  password: secret123\napi:\n  key: abc123";
		file_put_contents( $tempPlaintextPath, $testData );
		$this->secretManager->encrypt( $tempPlaintextPath, $credentialsPath, $keyPath );
		unlink( $tempPlaintextPath );

		// Mock input and output
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $this->testConfigPath],
				['env', null, null],
				['key', null, null]
			] );

		$input->expects( $this->any() )
			->method( 'hasOption' )
			->willReturnMap( [
				['force', false],
				['verbose', false]
			] );

		$output->expects( $this->once() )
			->method( 'section' )
			->with( 'Base Secrets' );

		$output->expects( $this->once() )
			->method( 'write' )
			->with( $this->stringContains( 'database:' ) );

		$output->expects( $this->once() )
			->method( 'warning' )
			->with( 'Remember: Never share or commit decrypted secrets!' );

		$this->command->setInput( $input );
		$this->command->setOutput( $output );

		// Execute should succeed
		$result = $this->command->execute();
		$this->assertEquals( 0, $result );
	}

	/**
	 * Test showing specific key
	 */
	public function testExecuteShowsSpecificKey(): void
	{
		// Create test secrets
		$keyPath = $this->testConfigPath . '/master.key';
		$credentialsPath = $this->testConfigPath . '/secrets.yml.enc';

		$key = $this->secretManager->generateKey( $keyPath );

		$tempPlaintextPath = $this->testConfigPath . '/temp_plaintext.yml';
		$testData = "database:\n  password: secret123\napi:\n  key: abc123";
		file_put_contents( $tempPlaintextPath, $testData );
		$this->secretManager->encrypt( $tempPlaintextPath, $credentialsPath, $keyPath );
		unlink( $tempPlaintextPath );

		// Mock input and output
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $this->testConfigPath],
				['env', null, null],
				['key', null, 'database']
			] );

		$input->expects( $this->any() )
			->method( 'hasOption' )
			->willReturnMap( [
				['force', false],
				['verbose', false]
			] );

		$output->expects( $this->once() )
			->method( 'write' )
			->with( $this->logicalAnd(
				$this->stringContains( 'database:' ),
				$this->logicalNot( $this->stringContains( 'api:' ) )
			) );

		$this->command->setInput( $input );
		$this->command->setOutput( $output );

		// Execute should succeed
		$result = $this->command->execute();
		$this->assertEquals( 0, $result );
	}

	/**
	 * Test error when secrets file not found
	 */
	public function testExecuteErrorWhenSecretsFileNotFound(): void
	{
		// Mock input and output
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $this->testConfigPath],
				['env', null, null],
				['key', null, null]
			] );

		$input->expects( $this->any() )
			->method( 'hasOption' )
			->willReturnMap( [
				['force', false]
			] );

		$credentialsPath = $this->testConfigPath . '/secrets.yml.enc';

		$output->expects( $this->once() )
			->method( 'error' )
			->with( "Secrets file not found: {$credentialsPath}" );

		$output->expects( $this->once() )
			->method( 'info' )
			->with( "Use 'neuron secrets:edit' to create it." );

		$this->command->setInput( $input );
		$this->command->setOutput( $output );

		// Execute should fail
		$result = $this->command->execute();
		$this->assertEquals( 1, $result );
	}

	/**
	 * Helper to remove directory recursively
	 */
	private function removeDirectory( string $dir ): void
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
}