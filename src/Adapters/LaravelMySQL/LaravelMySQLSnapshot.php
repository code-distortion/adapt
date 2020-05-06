<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\Interfaces\SnapshotInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelHelperTrait;
use CodeDistortion\Adapt\Exceptions\AdaptSnapshotException;

/**
 * Database-adapter methods related to managing Laravel/MySQL database snapshots.
 */
class LaravelMySQLSnapshot implements SnapshotInterface
{
    use InjectTrait, LaravelHelperTrait;

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

        if (!$this->di->exec->commandRuns($this->config->mysqlExecutablePath.' --version')) {
            throw AdaptSnapshotException::mysqlClientNotPresent($this->config->mysqlExecutablePath);
        }

        $command = $this->config->mysqlExecutablePath.' '
            .'--host='.escapeshellarg($this->conVal('host')).' '
            .'--port='.escapeshellarg($this->conVal('port')).' '
            .'--user='.escapeshellarg($this->conVal('username')).' '
            .'--password='.escapeshellarg($this->conVal('password')).' '
            .escapeshellarg((string) $this->config->database).' '
            .'< '.escapeshellarg($path).' '
            .'2>/dev/null';

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
     * @return boolean
     * @throws AdaptSnapshotException Thrown when the snapshot export fails.
     */
    public function takeSnapshot(string $path): bool
    {
        if (!$this->di->exec->commandRuns($this->config->mysqldumpExecutablePath.' --version')) {
            throw AdaptSnapshotException::mysqldumpNotPresent($this->config->mysqldumpExecutablePath);
        }

        $command = $this->config->mysqldumpExecutablePath.' '
            .'--host='.escapeshellarg($this->conVal('host')).' '
            .'--port='.escapeshellarg($this->conVal('port')).' '
            .'--user='.escapeshellarg($this->conVal('username')).' '
            .'--password='.escapeshellarg($this->conVal('password')).' '
            .'--add-drop-table '
            .escapeshellarg((string) $this->config->database).' '
            .'> '.escapeshellarg($path).' '
            .'2>/dev/null';

        $this->di->exec->run($command, $output, $returnVal);
        if ($returnVal != 0) {
            throw AdaptSnapshotException::mysqlExportError($path, $returnVal);
        }
        return true;
    }
}
