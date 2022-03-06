<?php

namespace CodeDistortion\Adapt\Adapters;

use CodeDistortion\Adapt\Adapters\Interfaces\BuildInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\ConnectionInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\FindInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\NameInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseTransactionInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\ReuseJournalInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\SnapshotInterface;
use CodeDistortion\Adapt\Adapters\Interfaces\VerifierInterface;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Support\Hasher;

/**
 * A database-adapter for Laravel/MySQL.
 */
abstract class DBAdapter
{
    /** @var BuildInterface The "build" part of this database-adapter. */
    public BuildInterface $build;

    /** @var ConnectionInterface The "connection" part of this database-adapter. */
    public ConnectionInterface $connection;

    /** @var FindInterface The "finding" part of this database-adapter. */
    public FindInterface $find;

    /** @var NameInterface The "naming" part of this database-adapter. */
    public NameInterface $name;

    /** @var ReuseTransactionInterface The "reuse-transaction" part of this database-adapter. */
    public ReuseTransactionInterface $reuseTransaction;

    /** @var ReuseJournalInterface The "reuse-journal" part of this database-adapter. */
    public ReuseJournalInterface $reuseJournal;

    /** @var VerifierInterface The "verifier" part of this database-adapter. */
    public VerifierInterface $verifier;

    /** @var SnapshotInterface The "snapshot" part of this database-adapter. */
    public SnapshotInterface $snapshot;


    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     * @param Hasher      $hasher    The object used to generate and check hashes.
     */
    abstract public function __construct(DIContainer $di, ConfigDTO $configDTO, Hasher $hasher);
}
