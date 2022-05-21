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
     * Migrate the database.
     *
     * @param string|null $migrationsPath The location of the migrations.
     * @return void
     * @throws AdaptBuildException When the migrations couldn't be run.
     */
    protected function laravelMigrate($migrationsPath)
    {
        $logTimer = $this->di->log->newTimer();

        $useRealPath = false;
        if (!is_null($migrationsPath)) {

            // the --realpath option isn't available in Laravel < 5.6 so
            // relative paths (relative to base_path()) have to be passed
            $useRealPath = version_compare(Application::VERSION, '5.6.0', '>=');
            $migrationsPath = $useRealPath
                ? (string) realpath($migrationsPath)
                : $this->makeRealpathRelative((string) realpath($migrationsPath));
        }

        try {
            $this->di->artisan->call(
                'migrate',
                array_filter([
                    '--env' => 'testing',
                    '--database' => $this->configDTO->connection,
                    '--force' => true,
                    '--path' => $migrationsPath,
                    '--realpath' => ($useRealPath ? true : null),
                ])
            );
        } catch (Throwable $e) {
            throw AdaptBuildException::migrationsFailed($migrationsPath, $e);
        }

        $this->di->log->debug('Ran migrations', $logTimer);
    }

    /**
     * Run the given seeders.
     *
     * @param string[] $seeders The seeders to run.
     * @return void
     * @throws AdaptBuildException When the seeder couldn't be run.
     */
    protected function laravelSeed($seeders)
    {
        foreach ($seeders as $seeder) {

            $logTimer = $this->di->log->newTimer();

            $seeder = $this->resolveSeeder($seeder);

            try {
                $this->di->artisan->call(
                    'db:seed',
                    array_filter(
                        [
                            '--env' => 'testing',
                            '--database' => $this->configDTO->connection,
                            '--class' => $seeder,
                            '--no-interaction' => true,
                        ]
                    )
                );
            } catch (Throwable $e) {
                throw AdaptBuildException::seederFailed($seeder, $e);
            }

            $this->di->log->debug('Ran seeder "' . $seeder . '"', $logTimer);
        }
    }

    /**
     * Account for the old, no-namespace version of the default DatabaseSeeder.
     *
     * When $seed = true, Adapt picks the Database\Seeders\DatabaseSeeder class.
     * This won't exist for older versions of Laravel which didn't use a namespace
     * for seeders. This will account for that.
     *
     * @param string $seeder The seeder to be called.
     * @return string
     */
    private function resolveSeeder(string $seeder): string
    {
        if (class_exists($seeder)) {
            return $seeder;
        }

        $prefix = "\\Database\\Seeders\\";

        // e.g. turn "DatabaseSeeder" in to "Database\Seeders\DatabaseSeeder"
        $tempSeeder = ltrim($seeder, '\\');
        if (mb_strpos($tempSeeder, '\\') === false) {
            $newSeeder = $prefix . $tempSeeder;
            return class_exists($newSeeder) ? $newSeeder : $seeder;
        }

        // e.g. turn "Database\Seeders\DatabaseSeeder" in to "DatabaseSeeder"
        $tempSeeder = '\\' . ltrim($seeder, '\\');
        if (mb_strpos($tempSeeder, $prefix) === 0) {
            $newSeeder = mb_substr($tempSeeder, mb_strlen($prefix));
            $newSeeder = '\\' . ltrim($newSeeder, '\\');
            return class_exists($newSeeder) ? $newSeeder : $seeder;
        }

        return $seeder;
    }

    /**
     * Start the transaction that the test will be encapsulated in.
     *
     * @return void
     */
    protected function laravelApplyTransaction()
    {
        $closure = $this->di->dbTransactionClosure;
        if (is_callable($closure)) {
            $closure($this->configDTO->connection);
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
