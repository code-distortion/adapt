<?php

namespace CodeDistortion\Adapt\Adapters;

use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLBuild;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLConnection;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLFind;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLName;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLReuseMetaDataTable;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLReuseTransaction;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLReuseJournal;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLSnapshot;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLVerifier;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * A database-adapter for Laravel/MySQL.
 */
class LaravelMySQLAdapter extends DBAdapter
{
    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO)
    {
        $this->build = new LaravelMySQLBuild($di, $configDTO);
        $this->connection = new LaravelMySQLConnection($di, $configDTO);
        $this->find = new LaravelMySQLFind($di, $configDTO);
        $this->name = new LaravelMySQLName($di, $configDTO);
        $this->verifier = new LaravelMySQLVerifier($di, $configDTO);
        $this->reuseMetaData = new LaravelMySQLReuseMetaDataTable($di, $configDTO);
        $this->reuseTransaction = new LaravelMySQLReuseTransaction($di, $configDTO);
        $this->reuseJournal = new LaravelMySQLReuseJournal($di, $configDTO, $this->verifier);
        $this->snapshot = new LaravelMySQLSnapshot($di, $configDTO);
    }
}
