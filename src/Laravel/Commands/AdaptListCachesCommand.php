<?php

namespace CodeDistortion\Adapt\Laravel\Commands;

use CodeDistortion\Adapt\Boot\BootCommandLaravel;
use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\Support\CommandFunctionalityTrait;
use CodeDistortion\Adapt\Support\LaravelSupport;

/**
 * Command to list the Adapt snapshot and test-databases.
 */
class AdaptListCachesCommand extends AbstractAdaptCommand
{
    use CommandFunctionalityTrait;

    /** @var string The name and signature of the console command. */
    protected $signature = 'adapt:list';

    /** @var string The console command description. */
    protected $description = 'List Adapt\'s test-databases and snapshot files';



    /**
     * Carry out the console command work.
     *
     * @return void
     */
    public function performHandleWork(): void
    {
        $cacheListDTO = $this->getCacheList();

        LaravelSupport::newLaravelLogger()->vDebug("\n");

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
    private function listDatabases(CacheListDTO $cacheListDTO): void
    {
        if (!$cacheListDTO->databases) {
            return;
        }

        $canPurge = (new BootCommandLaravel())->canPurgeStaleThings();

        $this->info(PHP_EOL . 'Test-databases:' . PHP_EOL);
        foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {

            reset($databaseMetaDTOs);
            $key = key($databaseMetaDTOs);
            $driver = $databaseMetaDTOs[$key]->driver;

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
    private function listSnapshotPaths(CacheListDTO $cacheListDTO): void
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
