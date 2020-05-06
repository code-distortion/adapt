<?php

namespace CodeDistortion\Adapt\Laravel\Commands;

use CodeDistortion\Adapt\DI\Injectable\Filesystem;
use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\Support\CommandFunctionalityTrait;
use CodeDistortion\Adapt\Support\ReloadLaravelConfig;
use Illuminate\Console\Command;

/**
 * Command to delete the Adapt snapshot and test-databases.
 */
class AdaptRemoveCachesCommand extends Command
{
    use CommandFunctionalityTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adapt:remove-db-caches '
                            .'{--F|force} '
                            .'{--env-file=.env.testing : The .env file to load from}';

    /**
     * The console command description.
     *
     * @var string
     */
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
     * @return mixed
     */
    public function handle()
    {
        $envFile = (!is_array($this->option('env-file')) ? (string) $this->option('env-file') : '');

        $envPath = base_path().'/'.$envFile;
        (new ReloadLaravelConfig)->reload($envPath);

        $cacheListDTO = $this->getCacheList();
        if ($cacheListDTO->containsAnyCache()) {

            $delete = true;
            if (!$this->option('force')) {
                $this->listDatabases($cacheListDTO);
                $this->listSnapshotPaths($cacheListDTO);
                $delete = $this->confirm('Do you wish to proceed?');
            }
            if ($delete) {
                $this->deleteDatabases($cacheListDTO);
                $this->deleteSnapshots($cacheListDTO);
                $this->info('');
            }
        } else {
            $this->info('');
            $this->info('There are no caches to remove.');
            $this->info('');
        }
    }

    /**
     * List the databases found in the given CacheListDTO.
     *
     * @param CacheListDTO $cacheListDTO The CacheListDTO to get values from.
     * @return void
     */
    private function listDatabases(CacheListDTO $cacheListDTO): void
    {
        if ($cacheListDTO->databases) {
            $this->warn(PHP_EOL.'These test-databases will be DELETED:'.PHP_EOL);
            foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {
                $this->warn('- Connection "'.$connection.'":');
                foreach ($databaseMetaDTOs as $databaseMetaDTO) {
                    $this->warn('  - '.$databaseMetaDTO->readable());
                }
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
        if ($cacheListDTO->snapshots) {
            $this->warn(PHP_EOL.'These snapshots will be DELETED:'.PHP_EOL);
            foreach ($cacheListDTO->snapshots as $snapshotMetaDTO) {
                $this->warn('- '.$snapshotMetaDTO->readable());
            }
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
        if ($cacheListDTO->databases) {
            $this->info(PHP_EOL.'Test-databases:'.PHP_EOL);
            foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {
                $this->info('- Connection "'.$connection.'":');
                foreach ($databaseMetaDTOs as $databaseMetaDTO) {
                    if ($this->deleteDatabase((string) $connection, (string) $databaseMetaDTO->name)) {
                        $this->info('  - DELETED '.$databaseMetaDTO->readable());
                    } else {
                        $this->error('  - COULD NOT DELETE '.$databaseMetaDTO->readable());
                    }
                }
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
        if ($cacheListDTO->snapshots) {

            $fileSystem = new Filesystem;

            $this->info(PHP_EOL.'Snapshots:'.PHP_EOL);
            foreach ($cacheListDTO->snapshots as $snapshotMetaDTO) {
                if ($fileSystem->unlink((string) $snapshotMetaDTO->path)) {
                    $this->info('- DELETED '.$snapshotMetaDTO->readable());
                } else {
                    $this->error('- COULD NOT DELETE '.$snapshotMetaDTO->readable());
                }
            }
        }
    }
}
