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

    /**
     * The hash used when generating snapshot-paths.
     *
     * @var string|null
     */
    private ?string $snapshotHash = null;

    /**
     * The hash of the files that may affect the database - based on the files and directories in hashPaths.
     *
     * @var string|null
     */
    private static ?string $sourceFilesHash = null;


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
     * Generate the snapshot-hash representing this DatabaseBuilder's state.
     *
     * @return string
     */
    public function currentSnapshotHash(): string
    {
        $this->snapshotHash ??= $this->generateSnapshotHash($this->config->pickSeedersToInclude());
        return $this->snapshotHash;
    }

    /**
     * Generate a hash to be used in a snapshot filename.
     *
     * Based on the database-building file content, pre-migration-imports, migrations and seeder-settings.
     *
     * @param string[] $seeders The seeders that will be run.
     * @return string
     */
    public function generateSnapshotHash(array $seeders): string
    {
        $rest = [
            'preMigrationImports' => $this->config->preMigrationImports,
            'migrations' => $this->config->migrations,
            'seeders' => $seeders,
        ];
        $rest = md5(serialize($rest));

        return $this->generateSourceFilesHash().'-'.mb_substr($rest, 0, 16);
    }

    /**
     * Generate a hash to use in the database name.
     *
     * Based on the database-building file content, pre-migration-imports, migrations, seeder-settings, connection and
     * transactions.
     *
     * @param string[] $seeders The seeders that will be run.
     * @return string
     */
    public function generateDBNameHash(array $seeders): string
    {
        $rest = [
            'projectName' => $this->config->projectName,
            'preMigrationImports' => $this->config->preMigrationImports,
            'migrations' => $this->config->migrations,
            'seeders' => $seeders,
            'connection' => $this->config->connection,
            'transactions' => $this->config->transactions,
        ];
        $rest = md5(serialize($rest));

        return $this->generateSourceFilesHash().'-'.mb_substr($rest, 0, 16);
    }

    /**
     * Build a hash based on the files and directories in hashPaths.
     *
     * @return string
     * @throws AdaptConfigException Thrown when a directory or file could not be opened.
     */
    public function generateSourceFilesHash(): string
    {
        // only do the calculation the first time
        if (is_null(static::$sourceFilesHash)) {

            $logTimer = $this->di->log->newTimer();

            $hashPaths = $this->resolveHashPaths(
                $this->config->hashPaths,
                true,
                'databaseRelatedFilesPathInvalid'
            );

            $preMigratePaths = $this->resolveHashPaths(
                $this->config->pickPreMigrationDumps(),
                false,
                'preMigrationImportPathInvalid'
            );

            $migrationPaths = [];
            if (is_string($this->config->migrations)) {
                $migrationPaths = $this->resolveHashPaths(
                    [$this->config->migrations],
                    true,
                    'migrationsPathInvalid'
                );
            }

            $paths = array_unique(array_merge($hashPaths, $preMigratePaths, $migrationPaths));
            sort($paths);

            $hashes = [];
            foreach ($paths as $path) {
                $hashes[$path] = $this->di->filesystem->md5File($path);
            }

            $hash = md5(serialize($hashes));
            static::$sourceFilesHash = mb_substr($hash, 0, 16);

            $this->di->log->info('Generated a hash of the database-related files', $logTimer);
        }
        return (string) static::$sourceFilesHash;
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
    private function resolveHashPaths(array $paths, bool $dirAllowed, string $exceptionMethod): array
    {
        $resolvedPaths = [];
        foreach ($paths as $path) {
            $resolvedPaths = array_merge(
                $resolvedPaths,
                $this->resolveHashPath($path, $dirAllowed, $exceptionMethod)
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
    private function resolveHashPath(string $path, bool $dirAllowed, string $exceptionMethod): array
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
     * Check to see if the current files-hash is present in the filename
     *
     * @param string $filename The prefix that needs to be found.
     * @return boolean
     */
    public function filenameHasFilesHash(string $filename): bool
    {
        $filesHash = $this->generateSourceFilesHash();
        return (bool) preg_match(
            '/^.+\.'.preg_quote($filesHash).'[^0-9a-f][0-9a-f]+\.[^\.]+$/',
            $filename,
            $matches
        );
    }

    /**
     * Take the given snapshot-hash and return the files-hash from it.
     *
     * @param string $snapshotHash The snapshot-hash to inspect.
     * @return string|null
     */
    public function pickFileHashFromSnapshotHash(string $snapshotHash): ?string
    {
        // pick the first portion of the snapshot-hash - which is the files-hash
        if (preg_match(
            '/^([0-9a-f]+)[^0-9a-f][0-9a-f]+$/',
            $snapshotHash,
            $matches
        )) {
            return $matches[1];
        }
        return null;
    }
}
