<?php

namespace Tests\Data\Cli\Commands\Secrets\Key;

use Neuron\Data\Cli\Commands\Secrets\Key\GenerateCommand;
use PHPUnit\Framework\TestCase;
use Neuron\Cli\Input\Input;
use Neuron\Cli\Output\Output;

class GenerateCommandTest extends TestCase
{
	private string $testConfigPath;
	private GenerateCommand $command;

	protected function setUp(): void
	{
		parent::setUp();

		// Skip these tests until CLI dependency is available
		if( !class_exists( 'Neuron\Cli\Input\Input' ) )
		{
			$this->markTestSkipped( 'CLI component not available' );
		}

		// Create a temporary directory for testing
		$this->testConfigPath = sys_get_temp_dir() . '/test_secrets_generate_' . uniqid();
		mkdir( $this->testConfigPath, 0755, true );

		$this->command = new GenerateCommand();
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
		$this->assertEquals( 'secrets:key:generate', $this->command->getName() );
	}

	/**
	 * Test that the command has a description
	 */
	public function testGetDescription(): void
	{
		$this->assertEquals( 'Generate a new encryption key for secrets', $this->command->getDescription() );
	}

	/**
	 * Test generating master key
	 */
	public function testExecuteGeneratesMasterKey(): void
	{
		// Mock input and output
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $this->testConfigPath],
				['env', null, null]
			] );

		$input->expects( $this->any() )
			->method( 'hasOption' )
			->willReturnMap( [
				['force', false],
				['show', false],
				['verbose', false]
			] );

		$keyPath = $this->testConfigPath . '/master.key';

		$output->expects( $this->once() )
			->method( 'success' )
			->with( "Generated master key at: {$keyPath}" );

		$output->expects( $this->once() )
			->method( 'info' )
			->with( 'Next steps:' );

		$this->command->setInput( $input );
		$this->command->setOutput( $output );

		// Execute should succeed
		$result = $this->command->execute();
		$this->assertEquals( 0, $result );

		// Key file should exist
		$this->assertFileExists( $keyPath );

		// Key should be 64 hex characters
		$key = file_get_contents( $keyPath );
		$this->assertEquals( 64, strlen( $key ) );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/i', $key );
	}

	/**
	 * Test generating environment-specific key
	 */
	public function testExecuteGeneratesEnvironmentKey(): void
	{
		// Mock input and output
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $this->testConfigPath],
				['env', null, 'production']
			] );

		$input->expects( $this->any() )
			->method( 'hasOption' )
			->willReturnMap( [
				['force', false],
				['show', false],
				['verbose', false]
			] );

		$keyPath = $this->testConfigPath . '/secrets/production.key';

		$output->expects( $this->once() )
			->method( 'success' )
			->with( "Generated production environment key at: {$keyPath}" );

		$this->command->setInput( $input );
		$this->command->setOutput( $output );

		// Execute should succeed
		$result = $this->command->execute();
		$this->assertEquals( 0, $result );

		// Directory and key file should exist
		$this->assertDirectoryExists( $this->testConfigPath . '/secrets' );
		$this->assertFileExists( $keyPath );
	}

	/**
	 * Test error when key already exists without force
	 */
	public function testExecuteErrorWhenKeyExistsWithoutForce(): void
	{
		// Create an existing key file
		$keyPath = $this->testConfigPath . '/master.key';
		file_put_contents( $keyPath, 'existing_key' );

		// Mock input and output
		$input = $this->createMock( Input::class );
		$output = $this->createMock( Output::class );

		$input->expects( $this->any() )
			->method( 'getOption' )
			->willReturnMap( [
				['config', 'config', $this->testConfigPath],
				['env', null, null]
			] );

		$input->expects( $this->any() )
			->method( 'hasOption' )
			->willReturnMap( [
				['force', false],
				['show', false]
			] );

		$output->expects( $this->once() )
			->method( 'error' )
			->with( "Key file already exists: {$keyPath}" );

		$output->expects( $this->once() )
			->method( 'info' )
			->with( 'Use --force to overwrite the existing key.' );

		$output->expects( $this->once() )
			->method( 'warning' )
			->with( 'WARNING: Overwriting will make existing encrypted files unreadable!' );

		$this->command->setInput( $input );
		$this->command->setOutput( $output );

		// Execute should fail
		$result = $this->command->execute();
		$this->assertEquals( 1, $result );

		// Original key should still exist
		$this->assertEquals( 'existing_key', file_get_contents( $keyPath ) );
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