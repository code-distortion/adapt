<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\SnapshotInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;
use CodeDistortion\Adapt\Support\LaravelSupport;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/MySQL database snapshots.
 */
class LaravelMySQLSnapshot implements SnapshotInterface
{
    use InjectTrait;
    use LaravelHelperTrait;



    /** @var boolean|null An internal cache of whether the mysql client exists or not. */
    private static ?bool $mysqlClientExists = null;

    /** @var boolean|null An internal cache of whether mysqldump exists or not. */
    private static ?bool $mysqldumpExists = null;



    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps(): void
    {
        self::$mysqlClientExists = null;
        self::$mysqldumpExists = null;
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
    public function importSnapshot(string $path, bool $throwExceptionIfNotExists = false): bool
    {
        $realPath = LaravelSupport::basePath($path);

        if (!$this->di->filesystem->fileExists($realPath)) {
            if ($throwExceptionIfNotExists) {
                throw AdaptSnapshotException::importFileDoesNotExist($path);
            }
            return false;
        }

        $this->ensureMysqlClientExists();

        $command = $this->configDTO->mysqlExecutablePath . ' '
            . '--host=' . escapeshellarg($this->conVal('host')) . ' '
            . '--port=' . escapeshellarg($this->conVal('port')) . ' '
            . '--user=' . escapeshellarg($this->conVal('username')) . ' '
            . '--password=' . escapeshellarg($this->conVal('password')) . ' '
            . escapeshellarg((string) $this->configDTO->database) . ' '
            . '< ' . escapeshellarg($realPath);

        $this->di->exec->run($command, $output, $returnVal);

        $wasSuccessful = ($returnVal == 0);
        if (!$wasSuccessful) {
            throw AdaptSnapshotException::mysqlImportError($path, $returnVal, $output);
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
    public function takeSnapshot(string $path): void
    {
        $this->ensureMysqlDumpExists();

        $tmpPath = "$path.tmp." . mt_rand();

        $command = $this->configDTO->mysqldumpExecutablePath . ' '
            . '--host=' . escapeshellarg($this->conVal('host')) . ' '
            . '--port=' . escapeshellarg($this->conVal('port')) . ' '
            . '--user=' . escapeshellarg($this->conVal('username')) . ' '
            . '--password=' . escapeshellarg($this->conVal('password')) . ' '
            . '--add-drop-table '
            . '--skip-lock-tables '
            . escapeshellarg((string) $this->configDTO->database) . ' '
            . '> ' . escapeshellarg($tmpPath);

        $this->di->exec->run($command, $output, $returnVal);

        $wasSuccessful = ($returnVal == 0);
        if (!$wasSuccessful) {
            throw AdaptSnapshotException::mysqlExportError($path, $returnVal, $output);
        }

        $this->renameTempFile($tmpPath, $path);
    }

    /**
     * Make sure that the mysql client exists.
     *
     * @return void
     * @throws AdaptSnapshotException When the mysql client can't be run.
     */
    private function ensureMysqlClientExists(): void
    {
        self::$mysqlClientExists
            ??= $this->di->exec->commandRuns($this->configDTO->mysqlExecutablePath . ' --version');

        if (!self::$mysqlClientExists) {
            throw AdaptSnapshotException::mysqlClientNotPresent($this->configDTO->mysqlExecutablePath);
        }
    }

    /**
     * Make sure that mysqldump exists.
     *
     * @return void
     * @throws AdaptSnapshotException When mysqldump can't be run.
     */
    private function ensureMysqlDumpExists(): void
    {
        self::$mysqldumpExists
            ??= $this->di->exec->commandRuns($this->configDTO->mysqldumpExecutablePath . ' --version');

        if (!self::$mysqldumpExists) {
            throw AdaptSnapshotException::mysqldumpNotPresent($this->configDTO->mysqldumpExecutablePath);
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
    private function renameTempFile(string $tmpPath, string $path): void
    {
        try {
            if (!$this->di->filesystem->rename($tmpPath, $path)) {
                throw AdaptSnapshotException::mysqlExportErrorRenameTempFile($tmpPath, $path);
            }
        } catch (AdaptSnapshotException $e) {
            throw $e; // just rethrow as is
        } catch (Throwable $e) {
            throw AdaptSnapshotException::mysqlExportErrorRenameTempFile($tmpPath, $path, $e);
        }
    }
}
