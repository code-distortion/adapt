<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Boot\BootCommandLaravel;
use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use PDOException;

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
        $bootCommandLaravel = new BootCommandLaravel();
        $cacheListDTO = new CacheListDTO();

        // find databases
        foreach (array_keys(config('database.connections')) as $connection) {
            try {
                $builder = $bootCommandLaravel->makeNewBuilder((string) $connection);
                $databases = $builder->findDatabases(false, true, true);
                $cacheListDTO->databases((string) $connection, $databases);
            } catch (AdaptConfigException $e) {
                // ignore exceptions caused because the database can't be connected to
                // eg. other connections that aren't intended to be used. eg. 'pgsql', 'sqlsrv'
            } catch (PDOException $e) {
                // same as above
            }
        }

        // find snapshots
        $defaultConnection = config('database.default');
        $builder = $bootCommandLaravel->makeNewBuilder($defaultConnection);
        $cacheListDTO->snapshots($builder->findSnapshots());

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
        $builder = (new BootCommandLaravel())->makeNewBuilder((string) $connection);
        return $builder->removeDatabase($database);
    }
}
