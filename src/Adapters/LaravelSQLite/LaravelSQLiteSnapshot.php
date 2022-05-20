<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\SnapshotInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\SQLite\SQLiteHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/SQLite database snapshots.
 */
class LaravelSQLiteSnapshot implements SnapshotInterface
{
    use InjectTrait;
    use SQLiteHelperTrait;



    /**
     * Determine if a snapshot can be made from this database.
     *
     * @return boolean
     */
    public function supportsSnapshots(): bool
    {
        return !$this->isMemoryDatabase();
    }

    /**
     * Determine if snapshot files are simply copied when importing (e.g. for sqlite).
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
     * @param string  $path                      The location of the snapshot file.
     * @param boolean $throwExceptionIfNotExists Should an exception be thrown if the file doesn't exist?.
     * @return boolean
     * @throws AdaptSnapshotException When the import fails.
     */
    public function importSnapshot(string $path, bool $throwExceptionIfNotExists = false): bool
    {
        if (!$this->di->filesystem->fileExists($path)) {
            if ($throwExceptionIfNotExists) {
                throw AdaptSnapshotException::importFileDoesNotExist($path);
            }
            return false;
        }

        // disconnect to stop the
        // "PDOException: SQLSTATE[HY000]: General error: 17 database schema has changed"
        // exception on older versions
        $this->di->db->purge();

        try {
            if (!$this->di->filesystem->copy($path, (string) $this->configDTO->database)) {
                throw AdaptSnapshotException::importFailed($path);
            }
            return true;
        } catch (AdaptSnapshotException $e) {
            throw $e; // just rethrow as is
        } catch (Throwable $e) {
            throw AdaptSnapshotException::importFailed($path, $e);
        }
    }

    /**
     * Export the database to the specified snapshot file.
     *
     * @param string $path The location of the snapshot file.
     * @return void
     * @throws AdaptSnapshotException When the snapshot export fails.
     */
    public function takeSnapshot(string $path): void
    {
        try {
            if (!$this->di->filesystem->copy((string) $this->configDTO->database, $path)) {
                throw AdaptSnapshotException::SQLiteExportError((string) $this->configDTO->database, $path);
            }
        } catch (Throwable $e) {
            throw AdaptSnapshotException::SQLiteExportError((string) $this->configDTO->database, $path, $e);
        }
    }
}
