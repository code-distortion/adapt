<?php

namespace CodeDistortion\Adapt\Laravel\Commands;

use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\Support\CommandFunctionalityTrait;
use Throwable;

/**
 * Command to delete the Adapt snapshot and test-databases.
 */
class AdaptRemoveCachesCommand extends AbstractAdaptCommand
{
    use CommandFunctionalityTrait;

    /** @var string The name and signature of the console command. */
    protected $signature = 'adapt:remove '
                            . '{--F|force} ';

    /** @var string The console command description. */
    protected $description = 'Remove Adapt\'s test-databases and snapshot files';



    /**
     * Carry out the console command work.
     *
     * @return void
     */
    public function performHandleWork(): void
    {
        $cacheListDTO = $this->getCacheList();

        $log = $this->newLog();
        $log->vDebug("\n");

        if (!$cacheListDTO->containsAnyCache()) {
            $this->info('');
            $this->info('There are no databases or snapshot files to remove.');
            $this->info('');
            return;
        }

        if (!$this->getConfirmation($cacheListDTO)) {
            return;
        }

        $this->deleteDatabases($cacheListDTO);
        $this->deleteSnapshots($cacheListDTO);
        $this->info('');
        $log->vDebug("\n");
    }



    /**
     * Get confirmation from the user before proceeding.
     *
     * @param CacheListDTO $cacheListDTO The list of things to be deleted.
     * @return boolean
     */
    private function getConfirmation(CacheListDTO $cacheListDTO): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $this->listDatabases($cacheListDTO);
        $this->listSnapshotPaths($cacheListDTO);
        return $this->confirm('Do you wish to proceed? (use the --force option to skip)');
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

        $this->warn(PHP_EOL . 'These test-databases will be DELETED:' . PHP_EOL);
        foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {

            $driver = reset($databaseMetaDTOs)->driver;
            $this->warn("- Connection \"$connection\" (driver $driver):");

            foreach ($databaseMetaDTOs as $databaseMetaDTO) {
                $this->warn('  - ' . $databaseMetaDTO->readable());
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

        $this->warn(PHP_EOL . 'These snapshots will be DELETED:' . PHP_EOL);
        foreach ($cacheListDTO->snapshots as $snapshotMetaInfo) {
            $this->warn('- ' . $snapshotMetaInfo->readable());
        }
    }

    /**
     * Delete the databases found in the given CacheListDTO.
     *
     * @param CacheListDTO $cacheListDTO The CacheListDTO to get values from.
     * @return void
     */
    private function deleteDatabases(CacheListDTO $cacheListDTO): void
    {
        if (!$cacheListDTO->databases) {
            return;
        }

        // several databases may point to the same actual database.
        // get the sizes before deleting any of them
        foreach ($cacheListDTO->databases as $databaseMetaDTOs) {
            foreach ($databaseMetaDTOs as $databaseMetaDTO) {
                $databaseMetaDTO->getSize();
            }
        }

        $log = $this->newLog();

        $this->info(PHP_EOL . 'Test-databases:' . PHP_EOL);
        foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {

            $driver = reset($databaseMetaDTOs)->driver;
            $this->info("- Connection \"$connection\" (driver $driver):");

            foreach ($databaseMetaDTOs as $databaseMetaDTO) {

                $readable = null;
                $deleted = false;
                try {
                    $readable = $databaseMetaDTO->readable();
                    $deleted = $databaseMetaDTO->delete();
                } catch (Throwable $e) {
                }

                $deleted
                    ? $this->info("  - DELETED $readable")
                    : $this->error("  - COULD NOT DELETE $readable");

                $deleted
                    ? $log->debug("Deleted \"$connection\" database $readable")
                    : $log->error("Could not delete \"$connection\" database $readable");
            }
        }
    }

    /**
     * Delete the snapshots found in the given CacheListDTO.
     *
     * @param CacheListDTO $cacheListDTO The CacheListDTO to get values from.
     * @return void
     */
    private function deleteSnapshots(CacheListDTO $cacheListDTO): void
    {
        if (!$cacheListDTO->snapshots) {
            return;
        }

        $log = $this->newLog();

        $this->info(PHP_EOL . 'Snapshots:' . PHP_EOL);
        foreach ($cacheListDTO->snapshots as $snapshotMetaInfo) {

            $readable = null;
            $deleted = false;
            try {
                $readable = $snapshotMetaInfo->readable();
                $deleted = $snapshotMetaInfo->delete();
            } catch (Throwable $e) {
            }

            $deleted
                ? $this->info('- DELETED ' . $readable)
                : $this->error('- COULD NOT DELETE ' . $readable);

            $deleted
                ? $log->debug("Deleted snapshot $readable")
                : $log->error("Could not delete \snapshot $readable");
        }
    }
}
