<?php

namespace CodeDistortion\Adapt\Adapters;

use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLReuseJournal;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLVerifier;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLBuild;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLConnection;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLFind;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLName;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLReuseMetaDataTable;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLReuseTransaction;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLSnapshot;
use CodeDistortion\Adapt\Adapters\LaravelPostgreSQL\LaravelPostgreSQLVersion;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * A database-adapter for Laravel/PostgreSQL.
 */
class LaravelPostgreSQLAdapter extends DBAdapter
{
    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO)
    {
        $this->build = new LaravelPostgreSQLBuild($di, $configDTO);
        $this->connection = new LaravelPostgreSQLConnection($di, $configDTO);
        $this->find = new LaravelPostgreSQLFind($di, $configDTO);
        $this->name = new LaravelPostgreSQLName($di, $configDTO);
        $this->verifier = new LaravelPostgreSQLVerifier($di, $configDTO);
        $this->reuseMetaData = new LaravelPostgreSQLReuseMetaDataTable($di, $configDTO);
        $this->reuseTransaction = new LaravelPostgreSQLReuseTransaction($di, $configDTO);
        $this->reuseJournal = new LaravelPostgreSQLReuseJournal($di, $configDTO, $this->verifier);
        $this->snapshot = new LaravelPostgreSQLSnapshot($di, $configDTO);
        $this->version = new LaravelPostgreSQLVersion($di, $configDTO);
    }
}
