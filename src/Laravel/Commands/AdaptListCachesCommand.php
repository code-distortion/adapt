<?php

namespace CodeDistortion\Adapt\Laravel\Commands;

use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\Support\CommandFunctionalityTrait;
use CodeDistortion\Adapt\Support\ReloadLaravelConfig;
use CodeDistortion\Adapt\Support\StringSupport as Str;
use Illuminate\Console\Command;

/**
 * Command to list the Adapt snapshot and test-databases.
 */
class AdaptListCachesCommand extends Command
{
    use CommandFunctionalityTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adapt:list-db-caches '
                            .'{--env-file=.env.testing : The .env file to load from}';

    /**
     * The console command description.
     *
     * @var string
     */
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

        $envPath = base_path().'/'.$envFile;
        (new ReloadLaravelConfig)->reload($envPath);

        $cacheListDTO = $this->getCacheList();
        if ($cacheListDTO->containsAnyCache()) {
            $this->listDatabases($cacheListDTO);
            $this->listSnapshotPaths($cacheListDTO);
            $this->info('');
        } else {
            $this->info('');
            $this->info('There are no caches.');
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
            $this->info(PHP_EOL.'Test-databases:'.PHP_EOL);
            foreach ($cacheListDTO->databases as $connection => $databaseMetaDTOs) {
                $this->info('- Connection "'.$connection.'":');
                foreach ($databaseMetaDTOs as $databaseMetaDTO) {
                    $this->info('  - '.$databaseMetaDTO->readable());
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
            $this->info(PHP_EOL.'Snapshots:'.PHP_EOL);
            foreach ($cacheListDTO->snapshots as $snapshotMetaDTO) {
                $this->info('- '.$snapshotMetaDTO->readable());
            }
        }
    }
}
