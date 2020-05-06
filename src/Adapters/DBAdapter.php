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
    /**
     * The "build" part of this database-adapter.
     *
     * @var BuildInterface
     */
    public BuildInterface $build;

    /**
     * The "connection" part of this database-adapter.
     *
     * @var ConnectionInterface
     */
    public ConnectionInterface $connection;

    /**
     * The "naming" part of this database-adapter.
     *
     * @var NameInterface
     */
    public NameInterface $name;

    /**
     * The "reuse" part of this database-adapter.
     *
     * @var ReuseInterface
     */
    public ReuseInterface $reuse;

    /**
     * The "snapshot" part of this database-adapter.
     *
     * @var SnapshotInterface
     */
    public SnapshotInterface $snapshot;


    /**
     * Constructor.
     *
     * @param DIContainer $di     The dependency-injection container to use.
     * @param ConfigDTO   $config A DTO containing the settings to use.
     * @param Hasher      $hasher The object used to generate and check hashes.
     */
    abstract public function __construct(DIContainer $di, ConfigDTO $config, Hasher $hasher);
}
