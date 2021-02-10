<?php

namespace CodeDistortion\Adapt\Exceptions;

use CodeDistortion\Adapt\Support\Settings;
use Throwable;

/**
 * Exceptions generated when dealing with snapshot files.
 */
class AdaptSnapshotException extends AdaptException
{
    /**
     * Could not remove snapshot files.
     *
     * @param Throwable $originalException The originally thrown exception.
     * @return self
     */
    public static function couldNotRemoveSnapshots(Throwable $originalException)
    {
        return new self('Had trouble removing snapshot files', 0, $originalException);
    }

    /**
     * Could not find snapshot files.
     *
     * @param Throwable $originalException The originally thrown exception.
     * @return self
     */
    public static function hadTroubleFindingSnapshots(Throwable $originalException)
    {
        return new self('Had trouble finding snapshot files', 0, $originalException);
    }

    /**
     * Could not import a snapshot file.
     *
     * @param string $path The path of the file being imported.
     * @return self
     */
    public static function importFailed(string $path)
    {
        return new self('The import of "' . $path . '" failed');
    }

    /**
     * The MySQL client isn't available.
     *
     * @param string $path The path to the mysql executable.
     * @return self
     */
    public static function mysqlClientNotPresent(string $path)
    {
        return new self('The mysql client "' . $path . '" executable isn\'t available to use '
        . '(please check the ' . Settings::LARAVEL_CONFIG_NAME . '.database.mysql config settings)');
    }

    /**
     * The mysqldump executable isn't available.
     *
     * @param string $path The path to the mysql executable.
     * @return self
     */
    public static function mysqldumpNotPresent(string $path)
    {
        return new self('The mysqldump executable "' . $path . '" isn\'t available to use'
        . '(please check the ' . Settings::LARAVEL_CONFIG_NAME . '.database.mysql config settings)');
    }

    /**
     * The MySQL client gave an error while importing.
     *
     * @param string  $path      The file being imported from.
     * @param integer $returnVal The error code given by mysql.
     * @return self
     */
    public static function mysqlImportError(string $path, int $returnVal)
    {
        return new self('Could not import database from "' . $path . '" - the mysql return value was: ' . $returnVal);
    }

    /**
     * The MySQL client gave an error while exporting.
     *
     * @param string  $path      The file being imported from.
     * @param integer $returnVal The error code given by mysql.
     * @return self
     */
    public static function mysqlExportError(string $path, int $returnVal)
    {
        return new self('Could not export database to "' . $path . '" - the mysqldump return value was: ' . $returnVal);
    }

    /**
     * The MySQL client gave an error while exporting - when renaming the temp file.
     *
     * @param string         $srcPath           The path to the file being renamed.
     * @param string         $destPath          The path to be changed to.
     * @param Throwable|null $originalException The originally thrown exception.
     * @return self
     */
    public static function mysqlExportErrorRenameTempFile(string $srcPath, string $destPath, $originalException = null)
    {
        return $originalException
            ? new self("Could not rename temporary snapshot file $srcPath to $destPath", 0, $originalException)
            : new self("Could not rename temporary snapshot file $srcPath to $destPath");
    }

    /**
     * The MySQL client gave an error while exporting - when renaming the temp file.
     *
     * @param string $srcPath  The path to the file being renamed.
     * @param string $destPath The path to be changed to.
     * @return self
     */
    public static function SQLiteExportError(string $srcPath, string $destPath)
    {
        return new self("Could not export (copy) SQLite file from $srcPath to $destPath");
    }

    /**
     * Imports aren't allowed for the given driver/database.
     *
     * @param string $driver   The database driver to use when building the database ("mysql", "sqlite" etc).
     * @param string $database The name of the database being used.
     * @return self
     */
    public static function importsNotAllowed(string $driver, string $database)
    {
        return new self('Sorry, database imports aren\'t available for ' . $database . ' ' . $driver . ' databases');
    }
}
