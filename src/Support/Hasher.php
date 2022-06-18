<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;

/**
 * Injectable class to generate and check checksums.
 */
class Hasher
{
    use InjectTrait;

    /** @var string|null Checksum of all files that CAN be used to build databases - which may affect the db when changed. */
    private static ?string $buildChecksum = null;

    /** @var string[] Build-checksums of remote Adapt installations. */
    private static array $remoteBuildChecksums = [];

    /** @var string|null The snapshot scenario-checksum representing the way the database is to be built. */
    private ?string $currentSnapshotChecksum = null;

    /** @var string|null The scenario-checksum representing the way the database is to be built. */
    private ?string $currentScenarioChecksum = null;



    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps(): void
    {
        self::$buildChecksum = null;
        self::$remoteBuildChecksums = [];
    }



    /**
     * Allow the pre-calculated build-checksum to be passed in (if it has in fact been pre-calculated).
     *
     * @param string|null $buildChecksum The pre-calculated build-checksum (or null).
     * @return void
     */
    public static function buildChecksumWasPreCalculated(?string $buildChecksum): void
    {
        if (!$buildChecksum) {
            return;
        }
        self::$buildChecksum = $buildChecksum;
    }



    /**
     * A remote Adapt installation generated a build-checksum. Remember it for subsequent requests (to save time).
     *
     * @param string $remoteBuildUrl The remote-build url.
     * @param string $buildChecksum  The build-checksum that the remote Adapt installation calculated.
     * @return void
     */
    public static function rememberRemoteBuildChecksum(string $remoteBuildUrl, string $buildChecksum): void
    {
        self::$remoteBuildChecksums[$remoteBuildUrl] = $buildChecksum;
    }

    /**
     * Retrieve the cached remote-build checksum value (if it's been set).
     *
     * @param string $remoteBuildUrl The remote-build url.
     * @return string|null
     */
    public static function getRemoteBuildChecksum(string $remoteBuildUrl): ?string
    {
        return self::$remoteBuildChecksums[$remoteBuildUrl] ?? null;
    }



    /**
     * Generate the build-checksum part for snapshot filenames.
     *
     * @param boolean $useBuildChecksum Use the current build-checksum (if available).
     * @param boolean $force            Force the build-checksum to be generated, even if it's turned off via the
     *                                  config.
     * @return string
     */
    public function getBuildChecksumFilenamePart(bool $useBuildChecksum = true, bool $force = false): string
    {
        $buildChecksum = $useBuildChecksum && $this->getBuildChecksum($force)
            ? $this->getBuildChecksum($force)
            : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

        return mb_substr($buildChecksum, 0, 6);
    }



    /**
     * Resolve the current build-checksum.
     *
     * @param boolean $force Force the build-checksum to be generated, even if it's turned off via the config.
     * @return string|null
     * @throws AdaptConfigException When a directory or file could not be opened.
     */
    public function getBuildChecksum(bool $force = false): ?string
    {
        if ($force) {
            return self::$buildChecksum ??= $this->generateBuildChecksum();
        }

        if (!$this->configDTO->cacheInvalidationMethod || !$this->configDTO->dbSupportsReUse) {
            return null;
        }

        return self::$buildChecksum ??= $this->generateBuildChecksum();
    }

    /**
     * Build a checksum based on the source files (and the database name prefix).
     *
     * Note: database name "dby_xxxxxx_yyyyyyyyyyyy" - for the "x" part.
     * Note: snapshot file "snapshot.db.xxxxxx-yyyyyyyyyyyy.mysql" - for the "x" part.
     *
     * @return string
     * @throws AdaptConfigException When a directory or file could not be opened.
     */
    private function generateBuildChecksum(): string
    {
        $logTimer = $this->di->log->newTimer();

        $paths = $this->buildListOfBuildFiles();

        $buildChecksum = md5(serialize([
            'fileChecksum' => $this->checksumFiles($paths),
            'databasePrefix' => $this->configDTO->databasePrefix,
            'version' => Settings::REUSE_TABLE_VERSION,
        ]));

        $message = $this->configDTO->cacheInvalidationMethod == 'content'
            ? "Generated a content-based build-source checksum"
            : "Generated a modified-time based build-source checksum";

        $this->di->log->vDebug($message, $logTimer);

        return $buildChecksum;
    }

    /**
     * Generate a combined and sorted list of the "build" files.
     *
     * @return string[]
     */
    private function buildListOfBuildFiles(): array
    {
        $paths = array_unique(array_filter(array_merge(
            $this->resolveInitialImportPaths(),
            $this->resolveMigrationPaths(),
            $this->resolveChecksumFilePaths()
        )));
        $paths = $this->ignoreUnwantedChecksumFiles($paths);
        sort($paths);
        return $paths;
    }

    /**
     * Remove certain files from the list of files to generate a checksum from.
     *
     * @param array<string, string> $paths The list of files that were found.
     * @return string[]
     */
    private function ignoreUnwantedChecksumFiles(array $paths): array
    {
        // this helps avoid the problem testbench gives between versions
        // version 3.4
        // "vendor/orchestra/testbench-core/fixture/database/migrations/.gitkeep" => "d41d8cd98f00b204e9800998ecf8427e"
        // newer versions
        // "vendor/orchestra/testbench-core/laravel/database/migrations/.gitkeep" => "d41d8cd98f00b204e9800998ecf8427e"

        foreach ($paths as $index => $path) {
            $temp = (array) preg_split('/[\\\\\/]+/', $path);
            $filename = array_pop($temp);

            if (in_array($filename, ['.gitkeep'])) {
                unset($paths[$index]);
            }
        }
        return $paths;
    }

    /**
     * Look for paths to checksum from the checksum-paths list.
     *
     * @return string[]
     * @throws AdaptConfigException When a file does not exist or is a directory that shouldn't be used.
     */
    private function resolveChecksumFilePaths(): array
    {
        return $this->resolvePaths(
            $this->configDTO->checksumPaths,
            true,
            'databaseRelatedFilesPathInvalid'
        );
    }

    /**
     * Look for initial-import paths to checksum.
     *
     * @return string[]
     * @throws AdaptConfigException When a file does not exist or is a directory that shouldn't be used.
     */
    private function resolveInitialImportPaths(): array
    {
        return $this->resolvePaths(
            $this->configDTO->pickInitialImports(),
            false,
            'initialImportPathInvalid'
        );
    }

    /**
     * Look for migration paths to checksum.
     *
     * @return string[]
     * @throws AdaptConfigException When a file does not exist or is a directory that shouldn't be used.
     */
    private function resolveMigrationPaths(): array
    {
        $paths = is_string($this->configDTO->migrations)
            ? [database_path('migrations'), $this->configDTO->migrations]
            : [database_path('migrations')];
        $paths = array_unique($paths);

        return $this->resolvePaths($paths, true, 'migrationsPathInvalid');
    }

    /**
     * Look for paths to checksum.
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
     * Check that the given path is ready for checksum generation.
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
     * Take the list of files and generate a checksum for the contents of each.
     *
     * @param string[] $paths The files to checksum.
     * @return array<string, string|integer|null>
     */
    private function checksumFiles(array $paths): array
    {
        $checksums = [];
        foreach ($paths as $path) {
            $checksums[$path] = $this->configDTO->cacheInvalidationMethod == 'content'
                ? $this->di->filesystem->md5File($path)
                : $this->di->filesystem->fileModifiedTime($path);
        }
        return $checksums;
    }



    /**
     * Resolve the current snapshot scenario-checksum.
     *
     * @return string|null
     */
    public function currentSnapshotChecksum(): ?string
    {
        return $this->currentSnapshotChecksum
            ??= $this->generateSnapshotChecksum($this->configDTO->pickSeedersToInclude());
    }

    /**
     * Generate the snapshot scenario-checksum, based on the way this DatabaseBuilder will build this database.
     *
     * Note: snapshot file "snapshot.db.xxxxxx-yyyyyyyyyyyy.mysql" - for the "y" part.
     *
     * It's based on the database-building file content *that's being used in this situation*:
     * the current initial-imports, current migrations and current seeders.
     *
     * @param string[] $seeders The seeders that will be run.
     * @return string|null
     */
    private function generateSnapshotChecksum(array $seeders): ?string
    {
        if (!$this->configDTO->dbSupportsSnapshots) {
            return null;
        }

        return md5(serialize([
            'initialImports' => $this->configDTO->initialImports,
            'migrations' => $this->configDTO->migrations,
            'seeders' => $seeders,
            // todo - add journal / verification info if they added to snapshots later
//            'reuseJournal' => $this->configDTO->shouldUseJournal(),
//            'verifyStructure' => $this->configDTO->shouldVerifyStructure(),
//            'verifyData' => $this->configDTO->shouldVerifyData(),
        ]));
    }



    /**
     * Resolve the current scenario-checksum.
     *
     * @return string|null
     */
    public function currentScenarioChecksum(): ?string
    {
        return $this->currentScenarioChecksum
            ??= $this->generateScenarioChecksum($this->configDTO->pickSeedersToInclude());
    }

    /**
     * Generate an extended scenario checksum.
     *
     * Note: database name "dby_xxxxxx_yyyyyyyyyyyy" - for the "y" part.
     *
     * It's based on the settings *being used in this situation*: snapshot-checksum, project-name, original-db name,
     * is-browser-test setting, database reusability (transaction and journal) settings, and verification setting.
     *
     * @param string[] $seeders The seeders that will be run.
     * @return string|null
     */
    private function generateScenarioChecksum(array $seeders): ?string
    {
        if (!$this->configDTO->usingScenarios()) {
            return null;
        }

        return md5(serialize([
            'snapshotChecksum' => $this->generateSnapshotChecksum($seeders),
            'projectName' => $this->configDTO->projectName,
//            'connection' => $this->configDTO->connection, // not included, so that multiple connections can share
            'origDatabase' => $this->configDTO->origDatabase,
            'usingScenarios' => $this->configDTO->scenarios,
            'reuseTransaction' => $this->configDTO->shouldUseTransaction(),
            'reuseJournal' => $this->configDTO->shouldUseJournal(),
            'verifyStructure' => $this->configDTO->shouldVerifyStructure(),
            'verifyData' => $this->configDTO->shouldVerifyData(),
        ]));
    }



    /**
     * Check to see if the current build-checksum is present in the filename.
     *
     * e.g. "snapshot.db.ef7aa7-1e6855bc44ee.mysql".
     *
     * @param string $filename The prefix that needs to be found.
     * @return boolean
     */
    public function filenameHasBuildChecksum(string $filename): bool
    {
        // let the filename match the current build-checksum, and also the null build-checksum
        $buildChecksumParts = [
            $this->getBuildChecksumFilenamePart(true, true),
            $this->getBuildChecksumFilenamePart(false)
        ];

        foreach ($buildChecksumParts as $buildChecksumPart) {

            $matched = (bool) preg_match(
                '/^.+\.' . preg_quote($buildChecksumPart) . '[^0-9a-f][0-9a-f]+\.[^\.]+$/',
                $filename
            );

            if ($matched) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate a checksum to use in a snapshot filename.
     *
     * @param string[] $seeders The seeders that are included in the snapshot.
     * @return string
     */
    public function generateSnapshotFilenameChecksumPart(array $seeders): string
    {
        return $this->joinNameParts([
            $this->getBuildChecksumFilenamePart(),
            mb_substr((string) $this->generateSnapshotChecksum($seeders), 0, 12),
        ]);
    }

    /**
     * Generate a checksum to use in the database name.
     *
     * Based on the source-files checksum, extended-scenario checksum.
     *
     * @param string[] $seeders          The seeders that will be run.
     * @param string   $databaseModifier The modifier to use (e.g. ParaTest suffix).
     * @return string
     */
    public function generateDatabaseNameChecksumPart(array $seeders, string $databaseModifier): string
    {
        return $this->joinNameParts([
            $this->getBuildChecksumFilenamePart(),
            mb_substr((string) $this->generateScenarioChecksum($seeders), 0, 12),
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
        $parts = array_filter($parts, fn($value) => mb_strlen($value) > 0);
        return implode('-', $parts);
    }
}
