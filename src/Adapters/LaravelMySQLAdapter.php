<?php

namespace CodeDistortion\Adapt\Adapters;

use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLBuild;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLConnection;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLName;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLReuse;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLSnapshot;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Support\Hasher;

/**
 * A database-adapter for Laravel/MySQL.
 */
class LaravelMySQLAdapter extends DBAdapter
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
        $this->build = new LaravelMySQLBuild($di, $config);
        $this->connection = new LaravelMySQLConnection($di, $config);
        $this->name = new LaravelMySQLName($di, $config);
        $this->reuse = new LaravelMySQLReuse($di, $config, $hasher);
        $this->snapshot = new LaravelMySQLSnapshot($di, $config);
    }
}
