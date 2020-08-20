<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\SnapshotInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;

/**
 * Database-adapter methods related to managing Laravel/SQLite database snapshots.
 */
class LaravelSQLiteSnapshot implements SnapshotInterface
{
    use InjectTrait, LaravelHelperTrait, SQLiteHelperTrait;


    /**
     * Determine if a snapshot can be made from this database.
     *
     * @return boolean
     */
    public function isSnapshottable(): bool
    {
        return !$this->isMemoryDatabase();
    }

    /**
     * Determine if snapshot files are simply copied when importing (eg. for sqlite).
     *
     * @return boolean
     */
    public function snapshotFilesAreSimplyCopied(): bool
    {
        return true;
    }

    /**
     * Try and import the specified snapshot file.
     *
     * @param string  $path           The location of the snapshot file.
     * @param boolean $throwException Should an exception be thrown if the file doesn't exist?.
     * @return boolean
     * @throws AdaptSnapshotException Thrown when the import fails.
     */
    public function importSnapshot(string $path, bool $throwException = false): bool
    {
        if ((!$this->di->filesystem->fileExists($path))
        || (!$this->di->filesystem->copy($path, (string) $this->config->database))) {
            if ($throwException) {
                throw AdaptSnapshotException::importFailed($path);
            }
            return false;
        }
        return true;
    }

    /**
     * Export the database to the specified snapshot file.
     *
     * @param string $path The location of the snapshot file.
     * @return boolean
     */
    public function takeSnapshot(string $path): bool
    {
        return $this->di->filesystem->copy((string) $this->config->database, $path);
    }
}
