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
     * Could not import a snapshot file - because it doesn't exist.
     *
     * @param string $path The path of the file being imported.
     * @return self
     */
    public static function importFileDoesNotExist($path): self
    {
        return new self("The file \"$path\" does not exist to import");
    }

    /**
     * Could not import a snapshot file.
     *
     * @param string         $path              The path of the file being imported.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function importFailed($path, $previousException = null): self
    {
        return $previousException
            ? new self("The import of \"$path\" failed", 0, $previousException)
            : new self("The import of \"$path\" failed");
    }

    /**
     * The snapshot file could not be deleted.
     *
     * @param string         $path              The path of the snapshot file.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function deleteFailed($path, $previousException = null): self
    {
        return $previousException
            ? new self("The snapshot \"$path\" could not be removed", 0, $previousException)
            : new self("The snapshot \"$path\" could not be removed");
    }



    /**
     * The MySQL client isn't available.
     *
     * @param string $path The path to the mysql executable.
     * @return self
     */
    public static function mysqlClientNotPresent($path): self
    {
        return new self(
            "The mysql client \"$path\" executable isn't available to use "
            . "(please check the " . Settings::LARAVEL_CONFIG_NAME . ".database.mysql config settings)"
        );
    }

    /**
     * The mysqldump executable isn't available.
     *
     * @param string $path The path to the mysql executable.
     * @return self
     */
    public static function mysqldumpNotPresent($path): self
    {
        return new self(
            "The mysqldump executable \"$path\" isn't available to use "
            . "(please check the " . Settings::LARAVEL_CONFIG_NAME . ".database.mysql config settings)"
        );
    }

    /**
     * The MySQL client gave an error while importing.
     *
     * @param string   $path      The file being imported from.
     * @param integer  $returnVal The error code given by MySQL.
     * @param string[] $output    The output array generated by exec(..).
     * @return self
     */
    public static function mysqlImportError($path, $returnVal, $output): self
    {
        return new self(
            "Could not import database from \"$path\"" . PHP_EOL
            . "mysql output:" . PHP_EOL
            . implode(PHP_EOL, $output)
        );
    }

    /**
     * The MySQL client gave an error while exporting.
     *
     * @param string   $path      The file being imported from.
     * @param integer  $returnVal The error code given by MySQL.
     * @param string[] $output    The output array generated by exec(..).
     * @return self
     */
    public static function mysqlExportError($path, $returnVal, $output): self
    {
        return new self(
            "Could not export database to \"$path\"" . PHP_EOL
            . "mysqldump output:" . PHP_EOL
            . implode(PHP_EOL, $output)
        );
    }

    /**
     * The MySQL client gave an error while exporting - when renaming the temp file.
     *
     * @param string         $srcPath           The path to the file being renamed.
     * @param string         $destPath          The path to be changed to.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function mysqlExportErrorRenameTempFile(
        $srcPath,
        $destPath,
        $previousException = null
    ): self {

        $message = "Could not rename temporary snapshot file" . PHP_EOL
            . "from: $srcPath" . PHP_EOL
            . "to:   $destPath";

        return $previousException
            ? new self($message, 0, $previousException)
            : new self($message);
    }



    /**
     * The psql client isn't available.
     *
     * @param string $path The path to the psql executable.
     * @return self
     */
    public static function psqlClientNotPresent($path): self
    {
        return new self(
            "The psql client \"$path\" executable isn't available to use "
            . "(please check the " . Settings::LARAVEL_CONFIG_NAME . ".database.pgsql config settings)"
        );
    }

    /**
     * The pg_dump executable isn't available.
     *
     * @param string $path The path to the pg_dump executable.
     * @return self
     */
    public static function pgDumpNotPresent($path): self
    {
        return new self(
            "The pg_dump executable \"$path\" isn't available to use "
            . "(please check the " . Settings::LARAVEL_CONFIG_NAME . ".database.pgsql config settings)"
        );
    }

    /**
     * The PostgreSQL client gave an error while importing.
     *
     * @param string   $path      The file being imported from.
     * @param integer  $returnVal The error code given by PostgreSQL.
     * @param string[] $output    The output array generated by exec(..).
     * @return self
     */
    public static function pgsqlImportError($path, $returnVal, $output): self
    {
        return new self(
            "Could not import database from \"$path\"" . PHP_EOL
            . "psql output:" . PHP_EOL
            . implode(PHP_EOL, $output)
        );
    }

    /**
     * The PostgreSQL client gave an error while exporting.
     *
     * @param string   $path      The file being imported from.
     * @param integer  $returnVal The error code given by PostgreSQL.
     * @param string[] $output    The output array generated by exec(..).
     * @return self
     */
    public static function pgsqlExportError($path, $returnVal, $output): self
    {
        return new self(
            "Could not export database to \"$path\"" . PHP_EOL
            . "pg_dump output:" . PHP_EOL
            . implode(PHP_EOL, $output)
        );
    }

    /**
     * The PostgreSQL client gave an error while exporting - when renaming the temp file.
     *
     * @param string         $srcPath           The path to the file being renamed.
     * @param string         $destPath          The path to be changed to.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function pgsqlExportErrorRenameTempFile(
        $srcPath,
        $destPath,
        $previousException = null
    ): self {

        $message = "Could not rename temporary snapshot file $srcPath to $destPath";
        return $previousException
            ? new self($message, 0, $previousException)
            : new self($message);
    }



    /**
     * The SQLite client gave an error while exporting - when renaming the temp file.
     *
     * @param string         $srcPath           The path to the file being renamed.
     * @param string         $destPath          The path to be changed to.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function SQLiteExportError(
        $srcPath,
        $destPath,
        $previousException = null
    ): self {

        $message = "Could not export (copy) SQLite file from $srcPath to $destPath";
        return $previousException
            ? new self($message, 0, $previousException)
            : new self($message);
    }



    /**
     * Imports aren't allowed for the given driver/database.
     *
     * @param string $driver   The database driver to use when building the database ("mysql", "sqlite" etc).
     * @param string $database The name of the database being used.
     * @return self
     */
    public static function importsNotAllowed($driver, $database): self
    {
        return new self("Sorry, database imports aren't available for $database $driver databases");
    }
}
