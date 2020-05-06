<?php

namespace CodeDistortion\Adapt\Adapters\Traits\Laravel;

use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\LaravelSupport;
use Illuminate\Foundation\Application;
use Throwable;

/**
 * Database-adapter methods related to building a Laravel database.
 */
trait LaravelBuildTrait
{
    /**
     * Wipe the database.
     *
     * @return void
     */
    protected function wipeDB(): void
    {
        $logTimer = $this->di->log->newTimer();

        $artisan = $this->di->artisan;
        if ($artisan->commandExists('db:wipe')) {

            $this->di->artisan->call(
                'db:wipe',
                array_filter(
                    [
                        '--database' => $this->config->connection,
                        '--drop-views' => true,
                        '--drop-types' => ($this->config->driver == 'pgsql'),
                        '--force' => true,
                    ]
                )
            );
        } else {
            // @todo test dropAllTables when views exist, and Postgres Types exist
            $this->di->db->dropAllTables();
        }

        $this->di->log->info('Wiped database', $logTimer);
    }

    /**
     * Migrate the database.
     *
     * @param string|null $migrationsPath The location of the migrations.
     * @return void
     */
    protected function laravelMigrate(?string $migrationsPath): void
    {
        $logTimer = $this->di->log->newTimer();

        $laravelGte56 = version_compare(Application::VERSION, '5.6.0', '>=');

        // when running in Laravel < 5.6, the --realpath isn't available so paths relative to
        // base_path() have to be passed.
        //
        // when running in orchestra, the base_path() that the migrate command uses is
        // "/vendor/orchestra/testbench-core/src/Concerns/../../laravel".
        // prefixing the path with "../../../../" accounts for this.

        if (!is_null($migrationsPath)) {
            $orchestra = LaravelSupport::isRunningInOrchestra();
            $migrationsPath = $orchestra && !$laravelGte56
                ? '../../../../'.$this->di->filesystem->removeBasePath($migrationsPath)
                : realpath($migrationsPath);
        }

        $options = [
            '--database' => $this->config->connection,
            '--force' => true,
            '--path' => $migrationsPath,
            '--realpath' => ($laravelGte56 ? !is_null($migrationsPath) : null),
        ];

        $this->di->artisan->call('migrate', array_filter($options));

        $this->di->log->info('Migrated', $logTimer);
    }

    /**
     * Run the given seeders.
     *
     * @param string[] $seeders The seeders to run.
     * @return void
     * @throws AdaptBuildException Thrown when the seeder couldn't be run.
     */
    protected function laravelSeed(array $seeders): void
    {
        foreach ($seeders as $seeder) {

            $logTimer = $this->di->log->newTimer();

            try {
                $this->di->artisan->call(
                    'db:seed',
                    array_filter(
                        [
                            '--database' => $this->config->connection,
                            '--class' => $seeder,
                            '--force' => true,
                        ]
                    )
                );
            } catch (Throwable $e) {
                throw AdaptBuildException::seederFailed($seeder, $e);
            }

            $this->di->log->info('Ran seeder "'.$seeder.'"', $logTimer);
        }
    }

    /**
     * Start the transaction that the test will be encapsulated in.
     *
     * @return void
     */
    protected function laravelApplyTransaction(): void
    {
        $closure = $this->di->dbTransactionClosure;
        if (is_callable($closure)) {
            $closure($this->config->connection);
        }
    }
}
