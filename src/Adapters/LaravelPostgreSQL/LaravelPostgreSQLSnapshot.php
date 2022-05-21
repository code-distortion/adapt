<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\Interfaces\SnapshotInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/PostgreSQL database snapshots.
 */
class LaravelPostgreSQLSnapshot implements SnapshotInterface
{
    use InjectTrait;
    use LaravelHelperTrait;



    /** @var boolean|null An internal cache of whether the psql client exists or not. */
    private static $psqlClientExists;

    /** @var boolean|null An internal cache of whether pg_dump exists or not. */
    private static $pgdumpExists;



    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps()
    {
        self::$psqlClientExists = null;
        self::$pgdumpExists = null;
    }



    /**
     * Determine if a snapshot can be made from this database.
     *
     * @return boolean
     */
    public function supportsSnapshots(): bool
    {
        return true;
    }

    /**
     * Determine if snapshot files are simply copied when importing (e.g. for sqlite).
     *
     * @return boolean
     */
    public function snapshotFilesAreSimplyCopied(): bool
    {
        return false;
    }



    /**
     * Try and import the specified snapshot file.
     *
     * @param string  $path                      The location of the snapshot file.
     * @param boolean $throwExceptionIfNotExists Should an exception be thrown if the file doesn't exist?.
     * @return boolean
     * @throws AdaptSnapshotException When the import fails.
     */
    public function importSnapshot($path, $throwExceptionIfNotExists = false): bool
    {
        if (!$this->di->filesystem->fileExists($path)) {
            if ($throwExceptionIfNotExists) {
                throw AdaptSnapshotException::importFileDoesNotExist($path);
            }
            return false;
        }

        $this->ensurePsqlClientExists();

        $command = "PGPASSWORD=" . escapeshellarg($this->conVal('password')) . ' '
            . $this->configDTO->psqlExecutablePath . ' '
            . '--host=' . escapeshellarg($this->conVal('host')) . ' '
            . '--port=' . escapeshellarg($this->conVal('port')) . ' '
            . '--user=' . escapeshellarg($this->conVal('username')) . ' '
            . escapeshellarg((string) $this->configDTO->database) . ' '
            . '< ' . escapeshellarg($path);

        $this->di->exec->run($command, $output, $returnVal);

        $wasSuccessful = ($returnVal == 0);
        if (!$wasSuccessful) {
            throw AdaptSnapshotException::pgsqlImportError($path, $returnVal, $output);
        }
        return true;
    }

    /**
     * Export the database to the specified snapshot file.
     *
     * @param string $path The location of the snapshot file.
     * @return void
     * @throws AdaptSnapshotException When the snapshot export fails.
     */
    public function takeSnapshot($path)
    {
        $this->ensurePgDumpExists();

        $tmpPath = "$path.tmp." . mt_rand();

        $command = "PGPASSWORD=" . escapeshellarg($this->conVal('password')) . ' '
            . $this->configDTO->pgDumpExecutablePath . ' '
            . '--host=' . escapeshellarg($this->conVal('host')) . ' '
            . '--port=' . escapeshellarg($this->conVal('port')) . ' '
            . '--user=' . escapeshellarg($this->conVal('username')) . ' '
            . escapeshellarg((string) $this->configDTO->database) . ' '
            . '> ' . escapeshellarg($tmpPath);

        $this->di->exec->run($command, $output, $returnVal);

        $wasSuccessful = ($returnVal == 0);
        if (!$wasSuccessful) {
            throw AdaptSnapshotException::pgsqlExportError($path, $returnVal, $output);
        }

        $this->renameTempFile($tmpPath, $path);
    }

    /**
     * Make sure that the psql client exists.
     *
     * @return void
     * @throws AdaptSnapshotException When the psql client can't be run.
     */
    private function ensurePsqlClientExists()
    {
        self::$psqlClientExists = self::$psqlClientExists ?? $this->di->exec->commandRuns($this->configDTO->psqlExecutablePath . ' --version');

        if (!self::$psqlClientExists) {
            throw AdaptSnapshotException::psqlClientNotPresent($this->configDTO->psqlExecutablePath);
        }
    }

    /**
     * Make sure that pg_dump exists.
     *
     * @return void
     * @throws AdaptSnapshotException When pg_dump can't be run.
     */
    private function ensurePgDumpExists()
    {
        self::$pgdumpExists = self::$pgdumpExists ?? $this->di->exec->commandRuns($this->configDTO->pgDumpExecutablePath . ' --version');

        if (!self::$pgdumpExists) {
            throw AdaptSnapshotException::pgDumpNotPresent($this->configDTO->pgDumpExecutablePath);
        }
    }

    /**
     * Rename a temp export file.
     *
     * @param string $tmpPath The file that needs to be renamed.
     * @param string $path    The new file name.
     * @return void
     * @throws AdaptSnapshotException When renaming fails.
     */
    private function renameTempFile(string $tmpPath, string $path)
    {
        try {
            if (!$this->di->filesystem->rename($tmpPath, $path)) {
                throw AdaptSnapshotException::pgsqlExportErrorRenameTempFile($tmpPath, $path);
            }
        } catch (AdaptSnapshotException $e) {
            throw $e; // just rethrow as is
        } catch (Throwable $e) {
            throw AdaptSnapshotException::pgsqlExportErrorRenameTempFile($tmpPath, $path, $e);
        }
    }
}
