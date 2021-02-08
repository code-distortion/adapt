<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;

/**
 * Injectable class to generate and check hashes.
 */
class Hasher
{
    use InjectTrait;

    /** @var string|null The hash files that may affect the db - based on the files and dirs in hashPaths etc. */
    private static ?string $sourceFilesHash = null;

    /** @var string|null The hash representing the way the database is built. */
    private ?string $scenarioHash = null;


    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps(): void
    {
        static::$sourceFilesHash = null;
    }


    /**
     * Resolve the current source files hash.
     *
     * @return string
     * @throws AdaptConfigException Thrown when a directory or file could not be opened.
     */
    public function currentSourceFilesHash(): string
    {
        return static::$sourceFilesHash ??= $this->generateSourceFilesHash();
    }

    /**
     * Build a hash based on the source files (and the database name prefix).
     *
     * @return string
     * @throws AdaptConfigException Thrown when a directory or file could not be opened.
     */
    private function generateSourceFilesHash(): string
    {
        $logTimer = $this->di->log->newTimer();

        $paths = array_unique(array_filter(array_merge(
            $this->resolveHashFilePaths(),
            $this->resolvePreMigrationPaths(),
            $this->resolveMigrationPaths()
        )));
        sort($paths);

        $hashes = [];
        foreach ($paths as $path) {
            $hashes[$path] = $this->di->filesystem->md5File($path);
        }

        $sourceFilesHash = md5(serialize($hashes) . $this->config->databasePrefix);

        $this->di->log->info('Generated a hash of the database-related files', $logTimer);

        return $sourceFilesHash;
    }


    /**
     * Look for paths to hash from the hash-paths list.
     *
     * @return string[]
     * @throws AdaptConfigException Thrown when a file does not exist or is a directory that shouldn't be used.
     */
    private function resolveHashFilePaths(): array
    {
        return $this->resolvePaths(
            $this->config->hashPaths,
            true,
            'databaseRelatedFilesPathInvalid'
        );
    }

    /**
     * Look for pre-migration paths to hash.
     *
     * @return string[]
     * @throws AdaptConfigException Thrown when a file does not exist or is a directory that shouldn't be used.
     */
    private function resolvePreMigrationPaths(): array
    {
        return $this->resolvePaths(
            $this->config->pickPreMigrationDumps(),
            false,
            'preMigrationImportPathInvalid'
        );
    }

    /**
     * Look for migration paths to hash.
     *
     * @return string[]
     * @throws AdaptConfigException Thrown when a file does not exist or is a directory that shouldn't be used.
     */
    private function resolveMigrationPaths(): array
    {
        return $this->resolvePaths(
            is_string($this->config->migrations) ? [$this->config->migrations] : [],
            true,
            'migrationsPathInvalid'
        );
    }

    /**
     * Look for paths to hash.
     *
     * @param string[] $paths           A set of paths to use or look for files in.
     * @param boolean  $dirAllowed      Recurse into directories?.
     * @param string   $exceptionMethod The method to call if an exception needs to be returned.
     * @return string[]
     * @throws AdaptConfigException Thrown when a file does not exist or is a directory that shouldn't be used.
     */
    private function resolvePaths(array $paths, bool $dirAllowed, string $exceptionMethod): array
    {
        $resolvedPaths = [];
        foreach ($paths as $path) {
            $resolvedPaths = array_merge(
                $resolvedPaths,
                $this->resolvePath($path, $dirAllowed, $exceptionMethod)
            );
        }
        return $resolvedPaths;
    }

    /**
     * Check that the given path is ready for hashing.
     *
     * @param string  $path            The path to use or look for files in.
     * @param boolean $dirAllowed      Recurse into directories?.
     * @param string  $exceptionMethod The method to call if an exception needs to be returned.
     * @return string[]
     * @throws AdaptConfigException Thrown when the file does not exist or is a directory that shouldn't be used.
     */
    private function resolvePath(string $path, bool $dirAllowed, string $exceptionMethod): array
    {
        $realPath = $this->di->filesystem->realpath($path);
        if ((!$realPath) || (!$this->di->filesystem->pathExists($realPath))) {
            throw AdaptConfigException::$exceptionMethod($path);
        }

        if ($this->di->filesystem->isFile($realPath)) {
            return [$this->di->filesystem->removeBasePath($realPath)];
        }

        if (!$dirAllowed) {
            throw AdaptConfigException::$exceptionMethod($path);
        }

        $paths = $this->di->filesystem->filesInDir($realPath, true);
        foreach ($paths as $index => $path) {
            $paths[$index] = $this->di->filesystem->removeBasePath($path);
        }

        return $paths;
    }


    /**
     * Resolve the current scenario-hash.
     *
     * @return string
     */
    public function currentScenarioHash(): string
    {
        return $this->scenarioHash ??= $this->generateScenarioHash($this->config->pickSeedersToInclude());
    }

    /**
     * Generate the scenario-hash based on the way this DatabaseBuilder will build this database.
     *
     * Based on the database-building file content, database-name-prefix, pre-migration-imports, migrations and
     * seeder-settings.
     *
     * @param string[] $seeders The seeders that will be run.
     * @return string
     */
    private function generateScenarioHash(array $seeders): string
    {
        return md5(serialize([
            'preMigrationImports' => $this->config->preMigrationImports,
            'migrations' => $this->config->migrations,
            'seeders' => $seeders,
        ]));
    }


    /**
     * Generate a hash to use in the database name.
     *
     * Based on the database-building file content, database-name-prefix, pre-migration-imports, migrations,
     * seeder-settings, connection, transactions and isBrowserTest.
     *
     * @param string[] $seeders          The seeders that will be run.
     * @param string   $databaseModifier The modifier to use (eg. ParaTest suffix).
     * @return string
     */
    public function generateDBNameHash(array $seeders, string $databaseModifier): string
    {
        $databaseHash = md5(serialize([
            'scenarioHash' => $this->generateScenarioHash($seeders),
            'projectName' => $this->config->projectName,
            'connection' => $this->config->connection,
            'reuseTestDBs' => $this->config->reuseTestDBs,
            'browserTest' => $this->config->isBrowserTest,
        ]));

        return mb_substr($this->currentSourceFilesHash(), 0, 6)
            . '-'
            . mb_substr($databaseHash, 0, 12)
            . (mb_strlen($databaseModifier) ? "-$databaseModifier" : '');
    }


    /**
     * Generate a hash to use in a snapshot filename.
     *
     * @param string[] $seeders The seeders that are included in the snapshot.
     * @return string
     */
    public function generateSnapshotHash(array $seeders): string
    {
        $sourceFilesHash = $this->currentSourceFilesHash();
        $scenarioHash = $this->generateScenarioHash($seeders);

        return mb_substr($sourceFilesHash, 0, 6)
            . '-'
            . mb_substr($scenarioHash, 0, 12);
    }

    /**
     * Check to see if the current source-files-hash is present in the filename
     *
     * @param string $filename The prefix that needs to be found.
     * @return boolean
     */
    public function filenameHasSourceFilesHash(string $filename): bool
    {
        $sourceFilesHash = mb_substr($this->currentSourceFilesHash(), 0, 6);
        return (bool) preg_match(
            '/^.+\.' . preg_quote($sourceFilesHash) . '[^0-9a-f][0-9a-f]+\.[^\.]+$/',
            $filename,
            $matches
        );
    }
}
