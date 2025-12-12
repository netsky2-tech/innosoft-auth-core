<?php

namespace InnoSoft\AuthCore\Tests;

use InnoSoft\AuthCore\AuthCoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AuthCoreServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // env settings for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
