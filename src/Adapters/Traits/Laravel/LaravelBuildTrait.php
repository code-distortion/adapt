<?php

namespace CodeDistortion\Adapt\Adapters\Traits\Laravel;

use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use CodeDistortion\Adapt\Support\LaravelSupport;
use Illuminate\Database\Seeder;
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

        $useRealPath = false;
        if (!is_null($migrationsPath)) {

            // the --realpath option isn't available in Laravel < 5.6 so
            // relative paths (relative to base_path()) have to be passed
            $useRealPath = version_compare(Application::VERSION, '5.6.0', '>=');
            $migrationsPath = $useRealPath
                ? realpath($migrationsPath)
                : $this->makeRealpathRelative((string) realpath($migrationsPath));
        }

        $this->di->artisan->call(
            'migrate',
            array_filter([
                '--database' => $this->config->connection,
                '--force' => true,
                '--path' => $migrationsPath,
                '--realpath' => ($useRealPath ? true : null),
            ])
        );

        $this->di->log->info('Ran migrations', $logTimer);
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

            $seeder = $this->resolveSeeder($seeder);

            try {
                $this->di->artisan->call(
                    'db:seed',
                    array_filter(
                        [
                            '--database' => $this->config->connection,
                            '--class' => $seeder,
                            '--no-interaction' => true,
                        ]
                    )
                );
            } catch (Throwable $e) {
                throw AdaptBuildException::seederFailed($seeder, $e);
            }

            $this->di->log->info('Ran seeder "' . $seeder . '"', $logTimer);
        }
    }

    /**
     * Account for the old, no-namespace version of the default DatabaseSeeder.
     *
     * @param string $seeder The seeder to be called.
     * @return string
     */
    private function resolveSeeder(string $seeder): string
    {
        if ($this->isSeeder($seeder)) {
            return $seeder;
        }

        // e.g. turn "DatabaseSeeder" in to "Database\Seeders\DatabaseSeeder"
        if (mb_strpos($seeder, '\\') === false) {
            $newSeeder = "Database\\Seeders\\$seeder";
            return $this->isSeeder($newSeeder) ? $newSeeder : $seeder;
        }

        // e.g. turn "Database\Seeders\DatabaseSeeder" in to "DatabaseSeeder"
        $temp = explode('\\', $seeder);
        $newSeeder = end($temp);
        return $this->isSeeder($newSeeder) ? $newSeeder : $seeder;
    }

    /**
     * Check if the class exists and is a Seeder.
     *
     * @param string $class The class to check.
     * @return boolean
     */
    private function isSeeder(string $class): bool
    {
        return is_a($class, Seeder::class, true);
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

    /**
     * Take an absolute path and make it relative to the base project directory.
     *
     * @param string $path The path to alter.
     * @return string
     */
    private function makeRealpathRelative(string $path): string
    {
        $path = $this->di->filesystem->removeBasePath($path);

        // when running in orchestra, the base_path() is
        // "/vendor/orchestra/testbench-core/src/Concerns/../../laravel".
        // prefixing the path with "../../../../" accounts for this
        if (LaravelSupport::isRunningInOrchestra()) {
            $path = '../../../../' . $path;
        }
        return $path;
    }
}
