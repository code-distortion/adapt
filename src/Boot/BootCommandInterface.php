<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\DTO\PropBagDTO;

/**
 * Bootstrap Adapt for commands.
 */
interface BootCommandInterface
{
    /**
     * Create a new DatabaseBuilder object and set its initial values.
     *
     * @param string $connection The database connection to prepare.
     * @return DatabaseBuilder
     */
    public function makeNewBuilder(string $connection): DatabaseBuilder;
}
