<?php

namespace CodeDistortion\Adapt\Adapters;

use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteBuild;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteConnection;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteFind;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteName;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteReuseJournal;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteReuseTransaction;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteSnapshot;
use CodeDistortion\Adapt\Adapters\LaravelSQLite\LaravelSQLiteVerifier;
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
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     * @param Hasher      $hasher    The object used to generate and check hashes.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO, Hasher $hasher)
    {
        $this->build = new LaravelSQLiteBuild($di, $configDTO);
        $this->connection = new LaravelSQLiteConnection($di, $configDTO);
        $this->find = new LaravelSQLiteFind($di, $configDTO);
        $this->name = new LaravelSQLiteName($di, $configDTO);
        $this->verifier = new LaravelSQLiteVerifier($di, $configDTO);
        $this->reuseTransaction = new LaravelSQLiteReuseTransaction($di, $configDTO, $hasher);
        $this->reuseJournal = new LaravelSQLiteReuseJournal($di, $configDTO, $this->verifier);
        $this->snapshot = new LaravelSQLiteSnapshot($di, $configDTO);
    }
}
