<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;

/**
 * Database-adapter methods related to managing database snapshots.
 */
interface SnapshotInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di     The dependency-injection container to use.
     * @param ConfigDTO   $config A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $config);


    /**
     * Determine if a snapshot can be made from this database.
     *
     * @return boolean
     */
    public function isSnapshottable(): bool;

    /**
     * Determine if snapshot files are simply copied when importing (eg. for sqlite).
     *
     * @return boolean
     */
    public function snapshotFilesAreSimplyCopied(): bool;

    /**
     * Try and import the specified snapshot file.
     *
     * @param string  $path           The location of the snapshot file.
     * @param boolean $throwException Should an exception be thrown if the file doesn't exist?.
     * @return boolean
     * @throws AdaptSnapshotException Thrown when the import fails.
     */
    public function importSnapshot(string $path, bool $throwException = false): bool;

    /**
     * Export the database to the specified snapshot file.
     *
     * @param string $path The location of the snapshot file.
     * @return boolean
     * @throws AdaptSnapshotException Thrown when the snapshot export fails.
     */
    public function takeSnapshot(string $path): bool;
}
