<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Tests;

use Illuminate\Foundation\Application;
use Madgeek\Msg91\Msg91ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            Msg91ServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('msg91.driver', 'log');
        $app['config']->set('msg91.auth_key', 'test-auth-key');
        $app['config']->set('msg91.country_code', '91');
        $app['config']->set('msg91.sender_id', 'TESTSR');
        $app['config']->set('msg91.http', [
            'timeout' => 10,
            'retry' => 0,
        ]);
        $app['config']->set('msg91.whatsapp', [
            'integrated_number' => '919800000000',
            'namespace' => 'test-namespace',
            'language' => 'en',
        ]);
        $app['config']->set('msg91.otp', [
            'template_id' => 'otp-template',
            'expiry' => 5,
            'length' => 6,
        ]);
    }
}
