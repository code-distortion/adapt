<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Boot\BootCommandLaravel;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelLog;
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
        $bootCommandLaravel = (new BootCommandLaravel())->ensureStorageDirExists();
        $cacheListDTO = new CacheListDTO();

        $this->findDatabases($bootCommandLaravel, $cacheListDTO);
        $this->findSnapshots($bootCommandLaravel, $cacheListDTO);

        return $cacheListDTO;
    }

    /**
     * Find databases using the connections available.
     *
     * @param BootCommandLaravel $bootCommandLaravel The BootCommand object.
     * @param CacheListDTO       $cacheListDTO       The cache list that's being updated.
     * @return void
     */
    private function findDatabases(BootCommandLaravel $bootCommandLaravel, CacheListDTO $cacheListDTO)
    {
        $connections = LaravelSupport::configArray('database.connections');
        foreach (array_keys($connections) as $connection) {
            try {
                $builder = $bootCommandLaravel->makeNewBuilder((string) $connection);
                $cacheListDTO->databases((string) $connection, $builder->buildDatabaseMetaInfos());
            } catch (AdaptConfigException $e) {
                // ignore exceptions caused because the database can't be connected to
                // e.g. other connections that aren't intended to be used. e.g. 'sqlsrv'
            } catch (PDOException $e) {
                // same as above
            }
        }
    }

    /**
     * Find snapshot files.
     *
     * @param BootCommandLaravel $bootCommandLaravel The BootCommand object.
     * @param CacheListDTO       $cacheListDTO       The cache list that's being updated.
     * @return void
     */
    private function findSnapshots(BootCommandLaravel $bootCommandLaravel, CacheListDTO $cacheListDTO)
    {
        $connection = LaravelSupport::configString('database.default');
        $builder = $bootCommandLaravel->makeNewBuilder($connection);
        $cacheListDTO->snapshots($builder->buildSnapshotMetaInfos());
    }

    /**
     * Build a new Log instance.
     *
     * @return LogInterface
     */
    private function newLog(): LogInterface
    {
        return new LaravelLog((bool) config(Settings::LARAVEL_CONFIG_NAME . '.log.stdout'), (bool) config(Settings::LARAVEL_CONFIG_NAME . '.log.laravel'), (int) config(Settings::LARAVEL_CONFIG_NAME . '.log.verbosity'));
    }
}
