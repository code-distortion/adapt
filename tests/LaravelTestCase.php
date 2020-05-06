<?php

namespace CodeDistortion\Adapt\Tests;

//use CodeDistortion\Adapt\LaravelServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

/**
 * The test case that unit tests extend from.
 */
class LaravelTestCase extends TestCase
{

    /**
     * Get package providers.
     *
     * @param Application $app The Laravel app.
     * @return string[]
     */
    protected function getPackageProviders($app)
    {
//        return [
//            LaravelServiceProvider::class
//        ];
        return [];
    }
}
