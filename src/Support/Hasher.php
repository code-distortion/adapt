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
    private static $buildHash;

    /** @var string[] Build-hashes of remote Adapt installations. */
    private static $remoteBuildHashes = [];

    /** @var string|null The scenario-hash representing the way the database is to be built. */
    private $currentScenarioHash;

    /** @var string|null The snapshot scenario-hash representing the way the database is to be built. */
    private $currentSnapshotHash;



    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps()
    {
        self::$buildHash = null;
        self::$remoteBuildHashes = [];
    }



    /**
     * Allow the pre-calculated build-hash to be passed in (if it has in fact been pre-calculated).
     *
     * @param string|null $buildHash The pre-calculated build-hash (or null).
     * @return void
     */
    public static function buildHashWasPreCalculated($buildHash)
    {
        if (!$buildHash) {
            return;
        }
        self::$buildHash = $buildHash;
    }



    /**
     * A remote Adapt installation generated a build-hash. Remember it for subsequent requests (to save on build-time).
     *
     * @param string $remoteBuildUrl The remote-build url.
     * @param string $buildHash      The build-hash that the remote Adapt installation calculated.
     * @return void
     */
    public static function rememberRemoteBuildHash($remoteBuildUrl, $buildHash)
    {
        self::$remoteBuildHashes[$remoteBuildUrl] = $buildHash;
    }

    /**
     * Retrieve the cached remote-build hash value (if it's been set).
     *
     * @param string $remoteBuildUrl The remote-build url.
     * @return string|null
     */
    public static function getRemoteBuildHash($remoteBuildUrl)
    {
        return self::$remoteBuildHashes[$remoteBuildUrl] ?? null;
    }



    /**
     * Generate the build-hash part for snapshot filenames.
     *
     * @return string
     */
    public function getBuildHashFilenamePart(): string
    {
        $buildHash = $this->getBuildHash() ?: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
        return mb_substr($buildHash, 0, 6);
    }



    /**
     * Resolve the current build-hash.
     *
     * @return string
     * @throws AdaptConfigException When a directory or file could not be opened.
     */
    public function getBuildHash(): string
    {
        return self::$buildHash = self::$buildHash ?? $this->generateBuildHash();
    }

    /**
     * Build a hash based on the source files (and the database name prefix).
     *
     * Note: database name "dby_xxxxxx_yyyyyyyyyyyy" - for the "x" part.
     * Note: snapshot file "snapshot.db.xxxxxx-yyyyyyyyyyyy.mysql" - for the "x" part.
     *
     * @return string
     * @throws AdaptConfigException When a directory or file could not be opened.
     */
    private function generateBuildHash(): string
    {
        if (!$this->configDTO->checkForSourceChanges) {
            return '';
        }

        $logTimer = $this->di->log->newTimer();

        $paths = $this->buildListOfBuildFiles();
        $hashes = $this->hashFiles($paths);

        $buildHash = md5(serialize([
            'fileHashes' => $hashes,
            'databasePrefix' => $this->configDTO->databasePrefix,
            'version' => Settings::REUSE_TABLE_VERSION,
        ]));

        $this->di->log->debug(
            'Generated the build-hash - of the files that can be used to build the database',
            $logTimer
        );

        return $buildHash;
    }

    /**
     * Generate a combined and sorted list of the "build" files.
     *
     * @return string[]
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
     * @throws AdaptConfigException When a file does not exist or is a directory that shouldn't be used.
     */
    private function resolveHashFilePaths(): array
    {
        return $this->resolvePaths(
            $this->configDTO->hashPaths,
            true,
            'databaseRelatedFilesPathInvalid'
        );
    }

    /**
     * Look for pre-migration paths to hash.
     *
     * @return string[]
     * @throws AdaptConfigException When a file does not exist or is a directory that shouldn't be used.
     */
    private function resolvePreMigrationPaths(): array
    {
        return $this->resolvePaths(
            $this->configDTO->pickPreMigrationImports(),
            false,
            'preMigrationImportPathInvalid'
        );
    }

    /**
     * Look for migration paths to hash.
     *
     * @return string[]
     * @throws AdaptConfigException When a file does not exist or is a directory that shouldn't be used.
     */
    private function resolveMigrationPaths(): array
    {
        return $this->resolvePaths(
            is_string($this->configDTO->migrations) ? [$this->configDTO->migrations] : [],
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
     * @throws AdaptConfigException When a file does not exist or is a directory that shouldn't be used.
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
     * @throws AdaptConfigException When the file does not exist or is a directory that shouldn't be used.
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
     * Take the list of files and generate a hash for the contents of each.
     *
     * @param string[] $paths The files to hash.
     * @return array<string, string|null>
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
        return $this->currentSnapshotHash = $this->currentSnapshotHash ?? $this->generateSnapshotHash($this->configDTO->pickSeedersToInclude());
    }

    /**
     * Generate the snapshot scenario-hash, based on the way this DatabaseBuilder will build this database.
     *
     * Note: snapshot file "snapshot.db.xxxxxx-yyyyyyyyyyyy.mysql" - for the "y" part.
     *
     * It's based on the database-building file content *that's being used in this situation*:
     * the current pre-migration-imports, current migrations and current seeders.
     *
     * @param string[] $seeders The seeders that will be run.
     * @return string
     */
    private function generateSnapshotHash(array $seeders): string
    {
        return md5(serialize([
            'preMigrationImports' => $this->configDTO->preMigrationImports,
            'migrations' => $this->configDTO->migrations,
            'seeders' => $seeders,
//            'hasAppliedJournaling' => $hasAppliedJournaling, // @todo
        ]));
    }



    /**
     * Resolve the current scenario-hash.
     *
     * @return string
     */
    public function currentScenarioHash(): string
    {
        return $this->currentScenarioHash = $this->currentScenarioHash ?? $this->generateScenarioHash($this->configDTO->pickSeedersToInclude());
    }

    /**
     * Generate an extended scenario hash.
     *
     * Note: database name "dby_xxxxxx_yyyyyyyyyyyy" - for the "y" part.
     *
     * It's based on the settings *being used in this situation*: snapshot hash, project-name, original-database name,
     * is-browser-test setting, database reusability (transaction and journal) settings, and verification setting.
     *
     * @param string[] $seeders The seeders that will be run.
     * @return string
     */
    private function generateScenarioHash(array $seeders): string
    {
        return md5(serialize([
            'snapshotHash' => $this->generateSnapshotHash($seeders),
            'usingScenarios' => $this->configDTO->scenarioTestDBs,
            'projectName' => $this->configDTO->projectName,
//            'connection' => $this->configDTO->connection,
            'origDatabase' => $this->configDTO->origDatabase,
            'isBrowserTest' => $this->configDTO->isBrowserTest,
            'reuseTransaction' => $this->configDTO->shouldUseTransaction(),
            'reuseJournal' => $this->configDTO->shouldUseJournal(),
            'verifyStructure' => $this->configDTO->shouldVerifyStructure(),
            'verifyData' => $this->configDTO->shouldVerifyData(),
        ]));
    }



    /**
     * Check to see if the current build-hash is present in the filename
     *
     * @param string $filename The prefix that needs to be found.
     * @return boolean
     */
    public function filenameHasBuildHash($filename): bool
    {
        $buildHashPart = $this->getBuildHashFilenamePart();
        return (bool) preg_match(
            '/^.+\.' . preg_quote($buildHashPart) . '[^0-9a-f][0-9a-f]+\.[^\.]+$/',
            $filename
        );
    }

    /**
     * Generate a hash to use in a snapshot filename.
     *
     * @param string[] $seeders The seeders that are included in the snapshot.
     * @return string
     */
    public function generateSnapshotFilenameHashPart($seeders): string
    {
        return $this->joinNameParts([
            $this->getBuildHashFilenamePart(),
            mb_substr($this->generateSnapshotHash($seeders), 0, 12),
        ]);
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
    public function generateDatabaseNameHashPart($seeders, $databaseModifier): string
    {
        return $this->joinNameParts([
            $this->getBuildHashFilenamePart(),
            mb_substr($this->generateScenarioHash($seeders), 0, 12),
            $databaseModifier,
        ]);
    }

    /**
     * Take the parts of a name and stick them together.
     *
     * @param string[] $parts The parts of the name.
     * @return string
     */
    private function joinNameParts(array $parts): string
    {
        $parts = array_filter($parts, function ($value) {
            return mb_strlen($value) > 0;
        });
        return implode('-', $parts);
    }
}
