<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Tests;

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
}
