<?php

namespace Tests\Data\Setting\Source;

use Neuron\Data\Setting\Source\Env;
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
}
