<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Boot\BootCommandLaravel;
use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;

/**
 * Functionality used by the Laravel Commands
 */
trait CommandFunctionalityTrait
{
    /**
     * Retrieve a list of the snapshot and database caches that currently exist.
     *
     * @return CacheListDTO
     */
    protected function getCacheList(): CacheListDTO
    {
        $bootCommandLaravel = new BootCommandLaravel;
        $cacheListDTO = new CacheListDTO;

        // get snapshots
        $defaultConnection = config('database.default');
        $databaseBuilder = $bootCommandLaravel->makeNewBuilder($defaultConnection);
        $snapshotPaths = $databaseBuilder->findSnapshots(true, true);
        $cacheListDTO->snapshots($snapshotPaths);

        // get databases
        foreach (array_keys(config('database.connections')) as $connection) {
            try {
                $databaseBuilder = $bootCommandLaravel->makeNewBuilder((string) $connection);
                $databases = $databaseBuilder->findDatabases(false, true, true);
                $cacheListDTO->databases((string) $connection, $databases);
            } catch (AdaptConfigException $e) {
                // ignore exceptions caused because the database can't be connected to
                // eg. other connections that aren't intended to be used. eg. 'pgsql', 'sqlsrv'
            }
        }

        return $cacheListDTO;
    }

    /**
     * Remove the given database for the given connection.
     *
     * @param string $connection The connection the database is in.
     * @param string $database   The database to remove.
     * @return boolean
     */
    protected function deleteDatabase(string $connection, string $database): bool
    {
        $databaseBuilder = (new BootCommandLaravel)->makeNewBuilder((string) $connection);
        return $databaseBuilder->removeDatabase($database);
    }
}
