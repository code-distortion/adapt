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

    /** @var string|null Hash of all files that CAN be used to build databases - which may affect the db when changed. */
    private static ?string $buildHash = null;

    /** @var string|null The scenario-hash representing the way the database is to be built. */
    private ?string $currentScenarioHash = null;

    /** @var string|null The snapshot scenario-hash representing the way the database is to be built. */
    private ?string $currentSnapshotHash = null;



    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps(): void
    {
        self::$buildHash = null;
    }


    /**
     * Resolve the current build-hash.
     *
     * @return string
     * @throws AdaptConfigException Thrown when a directory or file could not be opened.
     */
    public function getBuildHash(): string
    {
        return self::$buildHash ??= $this->generateBuildHash();
    }

    /**
     * Build a hash based on the source files (and the database name prefix).
     *
     * @return string
     * @throws AdaptConfigException Thrown when a directory or file could not be opened.
     */
    private function generateBuildHash(): string
    {
        $logTimer = $this->di->log->newTimer();

        $paths = $this->buildListOfBuildFiles();
        $hashes = $this->hashFiles($paths);

        $buildHash = md5(serialize([
            'fileHashes' => $hashes,
            'databasePrefix' => $this->config->databasePrefix,
            'version' => Settings::REUSE_TABLE_VERSION,
        ]));

        $this->di->log->debug('Generated a build-hash - of the files that could be used to build the database', $logTimer);

        return $buildHash;
    }

    /**
     * Generate a combined and sorted list of the "build" files.
     *
     * @return array
     * @throws AdaptConfigException
     */
    private function buildListOfBuildFiles(): array
    {
        $paths = array_unique(array_filter(array_merge(
            $this->resolveHashFilePaths(),
            $this->resolvePreMigrationPaths(),
            $this->resolveMigrationPaths()
        )));
        sort($paths);
        return $paths;
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
            $this->config->pickPreMigrationImports(),
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
     * Take the list of files and generate a hash for each.
     *
     * @param string[] $paths The files to hash.
     * @return array<string, string>
     */
    private function hashFiles(array $paths): array
    {
        $hashes = [];
        foreach ($paths as $path) {
            $hashes[$path] = $this->di->filesystem->md5File($path);
        }
        return $hashes;
    }



    /**
     * Resolve the current snapshot scenario-hash.
     *
     * @return string
     */
    public function currentSnapshotHash(): string
    {
        return $this->currentSnapshotHash ??= $this->generateSnapshotHash($this->config->pickSeedersToInclude());
    }

    /**
     * Generate the snapshot scenario-hash, based on the way this DatabaseBuilder will build this database.
     *
     * It's based on the database-building file content: the current pre-migration-imports, current migrations and
     * current seeders.
     *
     * @param string[] $seeders The seeders that will be run.
     * @return string
     */
    private function generateSnapshotHash(array $seeders): string
    {
        return md5(serialize([
            'preMigrationImports' => $this->config->preMigrationImports,
            'migrations' => $this->config->migrations,
            'seeders' => $seeders,
        ]));
    }



    /**
     * Resolve the current scenario-hash.
     *
     * @return string
     */
    public function currentScenarioHash(): string
    {
        return $this->currentScenarioHash ??= $this->generateScenarioHash($this->config->pickSeedersToInclude());
    }

    /**
     * Generate an extended scenario hash.
     *
     * It's based on the snapshot hash, project-name, original-database name, database re-usability, and
     * is-browser-test setting.
     *
     * @param string[] $seeders The seeders that will be run.
     * @return string
     */
    private function generateScenarioHash(array $seeders): string
    {
        return md5(serialize([
            'snapshotHash' => $this->generateSnapshotHash($seeders),
            'projectName' => $this->config->projectName,
//            'connection' => $this->config->connection,
            'database' => $this->config->database,
            'reuseTestDBs' => $this->config->reuseTestDBs,
            'browserTest' => $this->config->isBrowserTest,
        ]));
    }



    /**
     * Check to see if the current build-hash is present in the filename
     *
     * @param string $filename The prefix that needs to be found.
     * @return boolean
     */
    public function filenameHasBuildHash(string $filename): bool
    {
        $buildHashPart = mb_substr($this->getBuildHash(), 0, 6);
        return (bool) preg_match(
            '/^.+\.' . preg_quote($buildHashPart) . '[^0-9a-f][0-9a-f]+\.[^\.]+$/',
            $filename,
            $matches
        );
    }

    /**
     * Generate a hash to use in a snapshot filename.
     *
     * @param string[] $seeders The seeders that are included in the snapshot.
     * @return string
     */
    public function generateSnapshotFilenameHashPart(array $seeders): string
    {
        return mb_substr($this->getBuildHash(), 0, 6)
            . '-'
            . mb_substr($this->generateSnapshotHash($seeders), 0, 12);
    }

    /**
     * Generate a hash to use in the database name.
     *
     * Based on the source-files hash, extended-scenario hash.
     *
     * @param string[] $seeders          The seeders that will be run.
     * @param string   $databaseModifier The modifier to use (e.g. ParaTest suffix).
     * @return string
     */
    public function generateDatabaseNameHashPart(array $seeders, string $databaseModifier): string
    {
        return mb_substr($this->getBuildHash(), 0, 6)
            . '-'
            . mb_substr($this->generateScenarioHash($seeders), 0, 12)
            . (mb_strlen($databaseModifier) ? "-$databaseModifier" : '');
    }
}
