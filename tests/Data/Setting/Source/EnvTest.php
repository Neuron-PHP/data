<?php

namespace Tests\Data\Setting\Source;

use Neuron\Data\Settings\Source\Env;
use Neuron\Data\Env as RealEnv;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset Env singleton if present to avoid persisted state between tests
        if (method_exists( RealEnv::class, 'getInstance' )) {
            $inst = RealEnv::getInstance();
            if ($inst !== null && method_exists($inst, 'reset')) {
                $inst->reset();
            }
        }

        // Clear any related env keys to avoid test pollution
        foreach (array_keys($_ENV) as $k) {
            if (strpos($k, 'DATABASE_') === 0 || strpos($k, 'LOG_') === 0 || strpos($k, 'MYAPP_') === 0) {
                unset($_ENV[$k]);
            }
        }
        foreach (array_keys($_SERVER) as $k) {
            if (strpos($k, 'DATABASE_') === 0 || strpos($k, 'LOG_') === 0 || strpos($k, 'MYAPP_') === 0) {
                unset($_SERVER[$k]);
            }
        }
    }

    public function testSectionDiscoveryAndRetrieval()
    {
        // Arrange: set environment keys using real Env and $_ENV so both getters work
        $realEnv = RealEnv::getInstance();
        $realEnv->put('DATABASE_HOST=127.0.0.1');
        $realEnv->put('DATABASE_PORT=3306');
        $realEnv->put('LOG_LEVEL=debug');

        // Ensure $_ENV entries exist so scanning finds them
        $_ENV['DATABASE_HOST'] = '127.0.0.1';
        $_ENV['DATABASE_PORT'] = '3306';
        $_SERVER['LOG_LEVEL'] = 'debug';

        $source = new Env($realEnv);

        // Act & Assert
        $sections = $source->getSectionNames();
        $this->assertContains('DATABASE', $sections);
        $this->assertContains('LOG', $sections);

        $dbNames = $source->getSectionSettingNames('database');
        $this->assertEquals(['host','port'], $dbNames);

        $dbSection = $source->getSection('database');
        $this->assertIsArray($dbSection);
        $this->assertSame('127.0.0.1', $dbSection['host']);
        $this->assertSame('3306', $dbSection['port']);

        // get single
        $host = $source->get('database','host');
        $this->assertSame('127.0.0.1', $host);
    }

    public function testGetSectionReturnsNullWhenMissing()
    {
        $realEnv = RealEnv::getInstance();

        $source = new Env($realEnv);

        $this->assertEmpty($source->getSectionSettingNames('myapp'));
        $this->assertNull($source->getSection('myapp'));
    }

    public function testSetWritesEnvAndGetReadsIt()
    {
        $realEnv = RealEnv::getInstance();

        $source = new Env($realEnv);

        $source->set('myapp','endpoint','https://example.test');

        // Ensure getenv/$_ENV reflect the change
        $_ENV['MYAPP_ENDPOINT'] = 'https://example.test';

        $this->assertSame('https://example.test', $source->get('myapp','endpoint'));

        $names = $source->getSectionSettingNames('myapp');
        $this->assertEquals(['endpoint'], $names);

        $section = $source->getSection('myapp');
        $this->assertIsArray($section);
        $this->assertSame('https://example.test', $section['endpoint']);
    }

    public function testJsonArrayParsing()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        // Test JSON array of strings
        $realEnv->put('PROCESSOR_PAYOUTS_PROCESSORS=["Forward","Fiserv","Infinicept","Grailpay Internal"]');
        $_ENV['PROCESSOR_PAYOUTS_PROCESSORS'] = '["Forward","Fiserv","Infinicept","Grailpay Internal"]';

        $processors = $source->get('processor_payouts', 'processors');

        $this->assertIsArray($processors);
        $this->assertCount(4, $processors);
        $this->assertEquals(['Forward', 'Fiserv', 'Infinicept', 'Grailpay Internal'], $processors);
    }

    public function testJsonArrayWithEmptyArray()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('TEST_EMPTY=[]');
        $_ENV['TEST_EMPTY'] = '[]';

        $result = $source->get('test', 'empty');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testJsonObjectParsing()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('TEST_CONFIG={"host":"localhost","port":3306}');
        $_ENV['TEST_CONFIG'] = '{"host":"localhost","port":3306}';

        $config = $source->get('test', 'config');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('host', $config);
        $this->assertArrayHasKey('port', $config);
        $this->assertEquals('localhost', $config['host']);
        $this->assertEquals(3306, $config['port']);
    }

    public function testCommaSeparatedArrayParsing()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('PROCESSOR_PAYOUTS_PROCESSORS=Forward,Fiserv,Infinicept,Grailpay Internal');
        $_ENV['PROCESSOR_PAYOUTS_PROCESSORS'] = 'Forward,Fiserv,Infinicept,Grailpay Internal';

        $processors = $source->get('processor_payouts', 'processors');

        $this->assertIsArray($processors);
        $this->assertCount(4, $processors);
        $this->assertEquals(['Forward', 'Fiserv', 'Infinicept', 'Grailpay Internal'], $processors);
    }

    public function testCommaSeparatedWithSpaces()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('TEST_LIST=  alpha  ,  beta  ,  gamma  ');
        $_ENV['TEST_LIST'] = '  alpha  ,  beta  ,  gamma  ';

        $result = $source->get('test', 'list');

        $this->assertIsArray($result);
        $this->assertEquals(['alpha', 'beta', 'gamma'], $result);
    }

    public function testSingleValueCommaSeparated()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        // Single value with trailing comma becomes two-element array
        $realEnv->put('TEST_SINGLE=value,');
        $_ENV['TEST_SINGLE'] = 'value,';

        $result = $source->get('test', 'single');

        $this->assertIsArray($result);
        $this->assertEquals(['value', ''], $result);
    }

    public function testBackwardCompatibilityPlainString()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('DATABASE_HOST=localhost');
        $_ENV['DATABASE_HOST'] = 'localhost';

        $result = $source->get('database', 'host');

        $this->assertIsString($result);
        $this->assertEquals('localhost', $result);
    }

    public function testBackwardCompatibilityUrl()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('API_ENDPOINT=https://example.com/api');
        $_ENV['API_ENDPOINT'] = 'https://example.com/api';

        $result = $source->get('api', 'endpoint');

        $this->assertIsString($result);
        $this->assertEquals('https://example.com/api', $result);
    }

    public function testBackwardCompatibilityNumber()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('DATABASE_PORT=3306');
        $_ENV['DATABASE_PORT'] = '3306';

        $result = $source->get('database', 'port');

        $this->assertIsString($result);
        $this->assertEquals('3306', $result);
    }

    public function testInvalidJsonReturnsAsString()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('TEST_INVALID=[this is not valid json]');
        $_ENV['TEST_INVALID'] = '[this is not valid json]';

        $result = $source->get('test', 'invalid');

        $this->assertIsString($result);
        $this->assertEquals('[this is not valid json]', $result);
    }

    public function testEmptyStringReturnsEmptyString()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('TEST_EMPTY=');
        $_ENV['TEST_EMPTY'] = '';

        $result = $source->get('test', 'empty');

        $this->assertIsString($result);
        $this->assertEquals('', $result);
    }

    public function testGetSectionWithMixedTypes()
    {
        $realEnv = RealEnv::getInstance();
        $source = new Env($realEnv);

        $realEnv->put('MIXED_STRING=hello');
        $realEnv->put('MIXED_ARRAY=["a","b","c"]');
        $realEnv->put('MIXED_CSV=one,two,three');

        $_ENV['MIXED_STRING'] = 'hello';
        $_ENV['MIXED_ARRAY'] = '["a","b","c"]';
        $_ENV['MIXED_CSV'] = 'one,two,three';

        $section = $source->getSection('mixed');

        $this->assertIsArray($section);
        $this->assertArrayHasKey('string', $section);
        $this->assertArrayHasKey('array', $section);
        $this->assertArrayHasKey('csv', $section);

        $this->assertIsString($section['string']);
        $this->assertEquals('hello', $section['string']);

        $this->assertIsArray($section['array']);
        $this->assertEquals(['a', 'b', 'c'], $section['array']);

        $this->assertIsArray($section['csv']);
        $this->assertEquals(['one', 'two', 'three'], $section['csv']);
    }
}
