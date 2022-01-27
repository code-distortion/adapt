<?php

namespace CodeDistortion\Adapt\Laravel\Commands;

use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\Support\CommandFunctionalityTrait;
use CodeDistortion\Adapt\Support\LaravelSupport;
use Illuminate\Console\Command;
use Throwable;

/**
 * Command to delete the Adapt snapshot and test-databases.
 */
class AdaptRemoveCachesCommand extends Command
{
    use CommandFunctionalityTrait;

    /** @var string The name and signature of the console command. */
    protected $signature = 'adapt:remove-db-caches '
                            . '{--F|force} '
                            . '{--env-file=.env.testing : The .env file to load from}';

    /** @var string The console command description. */
    protected $description = 'Remove Adapt\'s test-databases and snapshot files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $envFile = !is_array($this->option('env-file'))
            ? (string) $this->option('env-file')
            : '';

        LaravelSupport::useTestingConfig($envFile);

        $cacheListDTO = $this->getCacheList();
        if (!$cacheListDTO->containsAnyCache()) {
            $this->info('');
            $this->info('There are no caches to remove.');
            $this->info('');
            return;
        }

        if (!$this->getConfirmation($cacheListDTO)) {
            return;
        }

        $this->deleteDatabases($cacheListDTO);
        $this->deleteSnapshots($cacheListDTO);
        $this->info('');
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
        return $this->confirm('Do you wish to proceed?');
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

        $this->warn(PHP_EOL . 'These test-databases will be DELETED:' . PHP_EOL);
        foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {
            $this->warn('- Connection "' . $connection . '":');
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
    private function listSnapshotPaths(CacheListDTO $cacheListDTO)
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
    private function deleteDatabases(CacheListDTO $cacheListDTO)
    {
        if (!$cacheListDTO->databases) {
            return;
        }

        // several databases may point to the same actual database.
        // get the sizes before deleting any of them
        foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {
            foreach ($databaseMetaDTOs as $databaseMetaDTO) {
                $databaseMetaDTO->getSize();
            }
        }

        $this->info(PHP_EOL . 'Test-databases:' . PHP_EOL);
        foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {

            $this->info('- Connection "' . $connection . '":');
            foreach ($databaseMetaDTOs as $databaseMetaDTO) {

                $readable = null;
                $deleted = false;
                try {
                    $readable = $databaseMetaDTO->readable();
                    $deleted = $databaseMetaDTO->delete();
                } catch (Throwable $e) {
                }

                $deleted
                    ? $this->info('  - DELETED ' . $readable)
                    : $this->error('  - COULD NOT DELETE ' . $readable);
            }
        }
    }

    /**
     * Delete the snapshots found in the given CacheListDTO.
     *
     * @param CacheListDTO $cacheListDTO The CacheListDTO to get values from.
     * @return void
     */
    private function deleteSnapshots(CacheListDTO $cacheListDTO)
    {
        if (!$cacheListDTO->snapshots) {
            return;
        }

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
        }
    }
}
