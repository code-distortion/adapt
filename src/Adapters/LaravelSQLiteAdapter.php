<?php

namespace CodeDistortion\Adapt\Adapters;

use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteBuild;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteConnection;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteName;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteReuse;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteSnapshot;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Support\Hasher;

/**
 * A database-adapter for Laravel/SQLite.
 */
class LaravelSQLiteAdapter extends DBAdapter
{
    /**
     * Constructor.
     *
     * @param DIContainer $di     The dependency-injection container to use.
     * @param ConfigDTO   $config A DTO containing the settings to use.
     * @param Hasher      $hasher The object used to generate and check hashes.
     */
    public function __construct(DIContainer $di, ConfigDTO $config, Hasher $hasher)
    {
        $this->build = new LaravelSQLiteBuild($di, $config);
        $this->connection = new LaravelSQLiteConnection($di, $config);
        $this->name = new LaravelSQLiteName($di, $config);
        $this->reuse = new LaravelSQLiteReuse($di, $config, $hasher);
        $this->snapshot = new LaravelSQLiteSnapshot($di, $config);
    }
}
