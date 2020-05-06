<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Exec;
use CodeDistortion\Adapt\DI\Injectable\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\LaravelConfig;
use CodeDistortion\Adapt\DI\Injectable\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\LaravelLog;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Tests\Database\Seeders\DatabaseSeeder;
use DB;
use ErrorException;
use Exception;

trait DatabaseBuilderSetUpVoidTrait
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        DatabaseBuilder::resetStaticProps();
    }
}
