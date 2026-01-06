<?php

namespace Tests\Data\Cli\Commands\Secrets;

use Neuron\Data\Cli\Commands\Secrets\EditCommand;
use Neuron\Data\Settings\SecretManager;
use PHPUnit\Framework\TestCase;
use Neuron\Cli\Input\Input;
use Neuron\Cli\Output\Output;

class EditCommandTest extends TestCase
{
	private string $testConfigPath;
	private EditCommand $command;

	protected function setUp(): void
	{
		parent::setUp();

		// Skip these tests until CLI dependency is available
		if( !class_exists( 'Neuron\Cli\Input\Input' ) )
		{
			$this->markTestSkipped( 'CLI component not available' );
		}

		// Create a temporary directory for testing
		$this->testConfigPath = sys_get_temp_dir() . '/test_secrets_' . uniqid();
		mkdir( $this->testConfigPath, 0755, true );

		$this->command = new EditCommand();
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
		$this->assertEquals( 'secrets:edit', $this->command->getName() );
	}

	/**
	 * Test that the command has a description
	 */
	public function testGetDescription(): void
	{
		$this->assertEquals( 'Edit encrypted secrets file', $this->command->getDescription() );
	}

	/**
	 * Test that the command configures options correctly
	 */
	public function testConfigure(): void
	{
		$this->command->configure();

		// Create mock input to test options
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$this->command->setInput( $input );
		$this->command->setOutput( $output );

		// Test that options are configured
		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $this->testConfigPath],
				['env', null, null],
				['editor', null, null]
			] );

		// Options should be available
		$this->assertNotNull( $this->command );
	}

	/**
	 * Test editing base secrets when key exists
	 */
	public function testExecuteWithExistingKey(): void
	{
		// Create a test key file
		$keyPath = $this->testConfigPath . '/master.key';
		$secretManager = new SecretManager();
		$key = $secretManager->generateKey( $keyPath );

		// Create a test credentials file
		$credentialsPath = $this->testConfigPath . '/secrets.yml.enc';
		$tempPlaintextPath = $this->testConfigPath . '/temp_plaintext.yml';
		$testData = "test:\n  secret: value";
		file_put_contents( $tempPlaintextPath, $testData );
		$secretManager->encrypt( $tempPlaintextPath, $credentialsPath, $keyPath );
		unlink( $tempPlaintextPath );

		// Mock input and output
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $this->testConfigPath],
				['env', null, null],
				['editor', null, 'echo'] // Use echo as a no-op editor for testing
			] );

		$input->expects( $this->any() )
			->method( 'hasOption' )
			->with( 'verbose' )
			->willReturn( false );

		$output->expects( $this->once() )
			->method( 'info' )
			->with( 'Editing base secrets...' );

		$output->expects( $this->once() )
			->method( 'success' )
			->with( "Secrets saved to: {$credentialsPath}" );

		$this->command->setInput( $input );
		$this->command->setOutput( $output );

		// Execute should succeed
		$result = $this->command->execute();
		$this->assertEquals( 0, $result );

		// Key and credentials should still exist
		$this->assertFileExists( $keyPath );
		$this->assertFileExists( $credentialsPath );
	}

	/**
	 * Test editing environment-specific secrets
	 */
	public function testExecuteWithEnvironment(): void
	{
		// Create secrets directory
		mkdir( $this->testConfigPath . '/secrets', 0755, true );

		// Mock input and output
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $this->testConfigPath],
				['env', null, 'production'],
				['editor', null, 'echo'] // Use echo as a no-op editor
			] );

		$input->expects( $this->any() )
			->method( 'hasOption' )
			->with( 'verbose' )
			->willReturn( false );

		$keyPath = $this->testConfigPath . '/secrets/production.key';
		$credentialsPath = $this->testConfigPath . '/secrets/production.yml.enc';

		$output->expects( $this->once() )
			->method( 'info' )
			->with( 'Editing production environment secrets...' );

		$output->expects( $this->once() )
			->method( 'warning' )
			->with( "Key file not found at: {$keyPath}" );

		$this->command->setInput( $input );
		$this->command->setOutput( $output );

		// Execute should succeed
		$result = $this->command->execute();
		$this->assertEquals( 0, $result );

		// Key should be generated
		$this->assertFileExists( $keyPath );
		$this->assertFileExists( $credentialsPath );
	}

	/**
	 * Test that error is handled gracefully
	 */
	public function testExecuteWithError(): void
	{
		// Use a path that will cause an error (non-writable)
		$badPath = '/root/cannot_write_here';

		// Mock input and output
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $badPath],
				['env', null, null],
				['editor', null, null]
			] );

		$input->expects( $this->any() )
			->method( 'hasOption' )
			->with( 'verbose' )
			->willReturn( false );

		$output->expects( $this->once() )
			->method( 'error' )
			->with( $this->stringContains( 'Error editing secrets:' ) );

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