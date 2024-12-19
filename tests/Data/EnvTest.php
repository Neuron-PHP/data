<?php

namespace Data;

use Neuron\Data\Env;
use PHPUnit\Framework\TestCase;

/**
 * Derived from https://github.com/hakobyansen/phpenv
 */
class EnvTest extends TestCase
{
	private $_envFile;

	protected function setUp() : void
	{
		parent::setUp();

		$this->_envFile = 'examples/.env';
	}

	public function testEnv()
	{
		$env = Env::getInstance( $this->_envFile );

		$appEnv = preg_replace('/\s+/', ' ', trim($env->get('APP_ENV')));

		$this->assertEquals(
			'local', $appEnv
		);

		$appName = preg_replace('/\s+/', ' ', trim($env->get('APP_NAME')));

		$this->assertEquals(
			'The Phpenv Package', $appName
		);

		$this->assertEmpty(
			$env->get('NON_EXISTING_VAR')
		);
	}

	public function testEnvNoFile()
	{
		$env = Env::getInstance( 'does-not-exist' );

		putenv( "APP_NAME=The Phpenv Package" );
		putenv( "APP_ENV=local" );

		$appEnv = preg_replace('/\s+/', ' ', trim($env->get('APP_ENV')));

		$this->assertEquals(
			'local', $appEnv
		);

		$appName = preg_replace('/\s+/', ' ', trim($env->get('APP_NAME')));

		$this->assertEquals(
			'The Phpenv Package', $appName
		);

		$this->assertEmpty(
			$env->get('NON_EXISTING_VAR')
		);
	}

	public function testPut()
	{
		$env = Env::getInstance( $this->_envFile );

		$validConfig = 'APP_VALID=true';

		$env->put($validConfig);

		$this->assertEquals(
			'true', $env->get('APP_VALID')
		);

		$commentedOutConfig = '# APP_COMMENTED_OUT=dumb';

		$env->put($commentedOutConfig);

		$this->assertEmpty($env->get('APP_COMMENTED_OUT'));
		$this->assertEmpty($env->get('#APP_COMMENTED_OUT'));
		$this->assertEmpty($env->get('# APP_COMMENTED_OUT'));
	}
}
