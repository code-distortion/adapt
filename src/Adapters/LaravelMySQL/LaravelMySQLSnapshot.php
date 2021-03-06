<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\SnapshotInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;
use Throwable;

/**
 * Database-adapter methods related to managing Laravel/MySQL database snapshots.
 */
class LaravelMySQLSnapshot implements SnapshotInterface
{
    use InjectTrait;
    use LaravelHelperTrait;

    /** @var boolean|null An internal cache of whether the mysql client exists or not. */
    private static $mysqlClientExists = null;

    /** @var boolean|null An internal cache of whether mysqldump exists or not. */
    private static $mysqldumpExists = null;



    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps()
    {
        static::$mysqlClientExists = null;
        static::$mysqldumpExists = null;
    }


    /**
     * Determine if a snapshot can be made from this database.
     *
     * @return boolean
     */
    public function isSnapshottable(): bool
    {
        return true;
    }

    /**
     * Determine if snapshot files are simply copied when importing (eg. for sqlite).
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
     * @param string  $path           The location of the snapshot file.
     * @param boolean $throwException Should an exception be thrown if the file doesn't exist?.
     * @return boolean
     * @throws AdaptSnapshotException Thrown when the import fails.
     */
    public function importSnapshot(string $path, bool $throwException = false): bool
    {
        if (!$this->di->filesystem->fileExists($path)) {
            if ($throwException) {
                throw AdaptSnapshotException::importFailed($path);
            }
            return false;
        }
        $this->ensureMysqlClientExists();
        $command = $this->config->mysqlExecutablePath . ' '
            . '--host=' . escapeshellarg($this->conVal('host')) . ' '
            . '--port=' . escapeshellarg($this->conVal('port')) . ' '
            . '--user=' . escapeshellarg($this->conVal('username')) . ' '
            . '--password=' . escapeshellarg($this->conVal('password')) . ' '
            . escapeshellarg((string) $this->config->database) . ' '
            . '< ' . escapeshellarg($path) . ' '
            . '2>/dev/null';
        $this->di->exec->run($command, $output, $returnVal);
        if ($returnVal != 0) {
            if ($throwException) {
                throw AdaptSnapshotException::mysqlImportError($path, $returnVal);
            }
            return false;
        }
        return true;
    }

    /**
     * Export the database to the specified snapshot file.
     *
     * @param string $path The location of the snapshot file.
     * @return void
     * @throws AdaptSnapshotException Thrown when the snapshot export fails.
     */
    public function takeSnapshot(string $path)
    {
        $this->ensureMysqlDumpExists();
        $tmpPath = "$path.tmp." . mt_rand();
        $command = $this->config->mysqldumpExecutablePath . ' '
            . '--host=' . escapeshellarg($this->conVal('host')) . ' '
            . '--port=' . escapeshellarg($this->conVal('port')) . ' '
            . '--user=' . escapeshellarg($this->conVal('username')) . ' '
            . '--password=' . escapeshellarg($this->conVal('password')) . ' '
            . '--add-drop-table '
            . '--skip-lock-tables '
//          . '--ignore-table=' . escapeshellarg((string) $this->config->database . '.' . Settings::REUSE_TABLE) . ' '
            . escapeshellarg((string) $this->config->database) . ' '
            . '> ' . escapeshellarg($tmpPath) . ' '
            . '2>/dev/null';
        $this->di->exec->run($command, $output, $returnVal);
        if ($returnVal != 0) {
            throw AdaptSnapshotException::mysqlExportError($path, $returnVal);
        }
        try {
            if (!$this->di->filesystem->rename($tmpPath, $path)) {
                throw AdaptSnapshotException::mysqlExportErrorRenameTempFile($tmpPath, $path);
            }
        } catch (Throwable $e) {
            throw AdaptSnapshotException::mysqlExportErrorRenameTempFile($tmpPath, $path, $e);
        }
    }

    /**
     * Make sure that the mysql client exists.
     *
     * @return void
     * @throws AdaptSnapshotException Thrown when the mysql client can't be run.
     */
    private function ensureMysqlClientExists()
    {
        static::$mysqlClientExists = static::$mysqlClientExists ?? $this->di->exec->commandRuns($this->config->mysqlExecutablePath . ' --version');

        if (!static::$mysqlClientExists) {
            throw AdaptSnapshotException::mysqlClientNotPresent($this->config->mysqlExecutablePath);
        }
    }

    /**
     * Make sure that mysqldump exists.
     *
     * @return void
     * @throws AdaptSnapshotException Thrown when mysqldump can't be run.
     */
    private function ensureMysqlDumpExists()
    {
        static::$mysqldumpExists = static::$mysqldumpExists ?? $this->di->exec->commandRuns($this->config->mysqldumpExecutablePath . ' --version');

        if (!static::$mysqldumpExists) {
            throw AdaptSnapshotException::mysqldumpNotPresent($this->config->mysqldumpExecutablePath);
        }
    }
}
