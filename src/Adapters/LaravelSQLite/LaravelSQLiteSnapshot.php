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
    use InjectTrait;
    use LaravelHelperTrait;
    use SQLiteHelperTrait;


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
        try {
            if (!$this->di->filesystem->fileExists($path)) {
                throw AdaptSnapshotException::importFailed($path);
            }

            // disconnect to stop the
            // "PDOException: SQLSTATE[HY000]: General error: 17 database schema has changed"
            // exception on older versions
            $this->di->db->purge();

            if (!$this->di->filesystem->copy($path, (string) $this->config->database)) {
                throw AdaptSnapshotException::importFailed($path);
            }

            return true;

        } catch (AdaptSnapshotException $e) {
            if ($throwException) {
                throw $e;
            }
            return false;
        }
    }

    /**
     * Export the database to the specified snapshot file.
     *
     * @param string $path The location of the snapshot file.
     * @return void
     * @throws AdaptSnapshotException Thrown when the snapshot export fails.
     */
    public function takeSnapshot(string $path)
    {
        if (!$this->di->filesystem->copy((string) $this->config->database, $path)) {
            throw AdaptSnapshotException::SQLiteExportError((string) $this->config->database, $path);
        }
    }
}
