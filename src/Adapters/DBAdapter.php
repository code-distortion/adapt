<?php

namespace CodeDistortion\Adapt\Adapters;

use CodeDistortion\Adapt\Adapters\Interfaces\BuildInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\ConnectionInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\FindInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseMetaDataTableInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseTransactionInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseJournalInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\SnapshotInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\VerifierInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\VersionInterface;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * A database-adapter for Laravel/MySQL.
 */
abstract class DBAdapter
{
    /** @var BuildInterface The "build" part of this database-adapter. */
    public $build;

    /** @var ConnectionInterface The "connection" part of this database-adapter. */
    public $connection;

    /** @var FindInterface The "finding" part of this database-adapter. */
    public $find;

    /** @var NameInterface The "naming" part of this database-adapter. */
    public $name;

    /** @var ReuseMetaDataTableInterface The "reuse-meta-data" part of this database-adapter. */
    public $reuseMetaData;

    /** @var ReuseTransactionInterface The "reuse-transaction" part of this database-adapter. */
    public $reuseTransaction;

    /** @var ReuseJournalInterface The "reuse-journal" part of this database-adapter. */
    public $reuseJournal;

    /** @var VerifierInterface The "verifier" part of this database-adapter. */
    public $verifier;

    /** @var SnapshotInterface The "snapshot" part of this database-adapter. */
    public $snapshot;

    /** @var VersionInterface The "version" part of this database-adapter. */
    public $version;


    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    abstract public function __construct(DIContainer $di, ConfigDTO $configDTO);
}
