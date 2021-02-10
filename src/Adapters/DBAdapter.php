<?php

namespace CodeDistortion\Adapt\Adapters;

use CodeDistortion\Adapt\Adapters\Interfaces\BuildInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\ConnectionInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\SnapshotInterface;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Support\Hasher;

/**
 * A database-adapter for Laravel/MySQL.
 */
abstract class DBAdapter
{
    /** @var BuildInterface The "build" part of this database-adapter. */
    public $build;

    /** @var ConnectionInterface The "connection" part of this database-adapter. */
    public $connection;

    /** @var NameInterface The "naming" part of this database-adapter. */
    public $name;

    /** @var ReuseInterface The "reuse" part of this database-adapter. */
    public $reuse;

    /** @var SnapshotInterface The "snapshot" part of this database-adapter. */
    public $snapshot;


    /**
     * Constructor.
     *
     * @param DIContainer $di     The dependency-injection container to use.
     * @param ConfigDTO   $config A DTO containing the settings to use.
     * @param Hasher      $hasher The object used to generate and check hashes.
     */
    public abstract function __construct(DIContainer $di, ConfigDTO $config, Hasher $hasher);
}
