<?php

namespace CodeDistortion\Adapt\Laravel\Commands;

use CodeDistortion\Adapt\Boot\BootCommandLaravel;
use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\Support\CommandFunctionalityTrait;
use CodeDistortion\Adapt\Support\ReloadLaravelConfig;
use Illuminate\Console\Command;

/**
 * Command to list the Adapt snapshot and test-databases.
 */
class AdaptListCachesCommand extends Command
{
    use CommandFunctionalityTrait;

    /** @var string The name and signature of the console command. */
    protected $signature = 'adapt:list-db-caches { --env-file=.env.testing : The .env file to load from }';

    /** @var string The console command description. */
    protected $description = 'List Adapt\'s test-databases and snapshot files';

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
        (new ReloadLaravelConfig())->reload(base_path($envFile));

        $cacheListDTO = $this->getCacheList();
        if (!$cacheListDTO->containsAnyCache()) {
            $this->info('');
            $this->info('There are no caches.');
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

        $canPurge = (new BootCommandLaravel())->canPurgeInvalidThings();

        $this->info(PHP_EOL . 'Test-databases:' . PHP_EOL);
        foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {
            $this->info('- Connection "' . $connection . '":');
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

        $canPurge = (new BootCommandLaravel())->canPurgeInvalidThings();

        $this->info(PHP_EOL . 'Snapshots:' . PHP_EOL);
        foreach ($cacheListDTO->snapshots as $snapshotMetaInfo) {
            $this->info('- ' . $snapshotMetaInfo->readableWithPurgeInfo($canPurge));
        }
    }
}
