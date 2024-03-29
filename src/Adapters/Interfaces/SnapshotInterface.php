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
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO);



    /**
     * Determine if snapshots can be used with this database.
     *
     * @return boolean
     */
    public function supportsSnapshots(): bool;

    /**
     * Determine if snapshot files are simply copied when importing (e.g. for sqlite).
     *
     * @return boolean
     */
    public function snapshotFilesAreSimplyCopied(): bool;

    /**
     * Try and import the specified snapshot file.
     *
     * @param string  $path                      The location of the snapshot file.
     * @param boolean $throwExceptionIfNotExists Should an exception be thrown if the file doesn't exist?.
     * @return boolean
     * @throws AdaptSnapshotException When the import fails.
     */
    public function importSnapshot($path, $throwExceptionIfNotExists = false): bool;

    /**
     * Export the database to the specified snapshot file.
     *
     * @param string $path The location of the snapshot file.
     * @return void
     * @throws AdaptSnapshotException When the snapshot export fails.
     */
    public function takeSnapshot($path);
}
