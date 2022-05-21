<?php

namespace CodeDistortion\Adapt\Laravel\Commands;

use CodeDistortion\Adapt\Boot\BootCommandLaravel;
use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\Support\CommandFunctionalityTrait;

/**
 * Command to list the Adapt snapshot and test-databases.
 */
class AdaptListCachesCommand extends AbstractAdaptCommand
{
    use CommandFunctionalityTrait;

    /** @var string The name and signature of the console command. */
    protected $signature = 'adapt:list-db-caches';

    /** @var string The console command description. */
    protected $description = 'List Adapt\'s test-databases and snapshot files';



    /**
     * Carry out the console command work.
     *
     * @return void
     */
    public function performHandleWork()
    {
        $cacheListDTO = $this->getCacheList();

        if (!$cacheListDTO->containsAnyCache()) {
            $this->info('');
            $this->info('There are no databases or snapshot files.');
            $this->info('');
            return;
        }

        $this->listDatabases($cacheListDTO);
        $this->listSnapshotPaths($cacheListDTO);
        $this->info('');
    }



    /**
     * List the databases found in the given CacheListDTO.
     *
     * @param CacheListDTO $cacheListDTO The CacheListDTO to get values from.
     * @return void
     */
    private function listDatabases(CacheListDTO $cacheListDTO)
    {
        if (!$cacheListDTO->databases) {
            return;
        }

        $canPurge = (new BootCommandLaravel())->canPurgeStaleThings();

        $this->info(PHP_EOL . 'Test-databases:' . PHP_EOL);
        foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {

            $driver = reset($databaseMetaDTOs)->driver;
            $this->info("- Connection \"$connection\" (driver $driver):");

            foreach ($databaseMetaDTOs as $databaseMetaDTO) {
                $this->info('  - ' . $databaseMetaDTO->readableWithPurgeInfo($canPurge));
            }
        }
    }

    /**
     * List the snapshots found in the given CacheListDTO.
     *
     * @param CacheListDTO $cacheListDTO The CacheListDTO to get values from.
     * @return void
     */
    private function listSnapshotPaths(CacheListDTO $cacheListDTO)
    {
        if (!$cacheListDTO->snapshots) {
            return;
        }

        $this->info(PHP_EOL . 'Snapshots:' . PHP_EOL);
        foreach ($cacheListDTO->snapshots as $snapshotMetaInfo) {
            $this->info('- ' . $snapshotMetaInfo->readableWithPurgeInfo());
        }
    }
}
