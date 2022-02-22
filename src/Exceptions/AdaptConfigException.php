<?php

namespace CodeDistortion\Adapt\Exceptions;

use Throwable;

/**
 * Exceptions generated when dealing with config settings.
 */
class AdaptConfigException extends AdaptException
{
    /**
     * The .env.testing file could not be loaded.
     *
     * @return self
     */
    public static function cannotLoadEnvTestingFile(): self
    {
        return new self("The .env.testing file could not be loaded");
    }

    /**
     * The storage directory is a file.
     *
     * @param string $storageDir The storage directory.
     * @return self
     */
    public static function storageDirIsAFile($storageDir): self
    {
        return new self(
            "The storage directory \"$storageDir\" exists and is a file. Please review the \"storage_dir\" setting"
        );
    }

    /**
     * The storage directory could not be created.
     *
     * @param string         $storageDir        The storage directory.
     * @param Throwable|null $originalException The originally thrown exception.
     * @return self
     */
    public static function cannotCreateStorageDir($storageDir, $originalException = null): self
    {
        $message = "Could not create the storage directory \"$storageDir\". Please review the \"storage_dir\" setting";
        return $originalException
            ? new self($message, 0, $originalException)
            : new self($message);
    }

    /**
     * A pre-migration-import path could not be read.
     *
     * @param string $path The invalid path.
     * @return self
     */
    public static function preMigrationImportPathInvalid($path): self
    {
        return new self(
            "Couldn't open pre-migration-dump file \"$path\". "
            . "Please review the \"pre_migration_imports\" config setting"
        );
    }

    /**
     * The migrations path could not be read.
     *
     * @param string $path The invalid path.
     * @return self
     */
    public static function migrationsPathInvalid($path): self
    {
        return new self(
            "The migrations directory \"$path\" does not exist. Please review the \"migrations\" config setting"
        );
    }

    /**
     * A database-related files path could not be read.
     *
     * @param string $path The invalid path.
     * @return self
     */
    public static function databaseRelatedFilesPathInvalid($path): self
    {
        return new self(
            "Couldn't open file or directory \"$path\". Please review the \"look_for_changes_in\" config setting"
        );
    }

    /**
     * The seeders directory does not exist.
     *
     * @param string $path The invalid path.
     * @return self
     */
    public static function seedersDirInvalid($path): self
    {
        return new self(
            "Couldn't open file or directory \"$path\". "
            . "Please review the \"look_for_changes_in\" config setting. "
            . "Note: Laravel renamed the seeders directory from \"database/seeds\" to \"database/seeders\" in Laravel 8"
        );
    }


    /**
     * The connection details couldn't be read.
     *
     * @param string $connection The connection used.
     * @return self
     */
    public static function couldNotReadConnectionDetails($connection): self
    {
        return new self("Could not read connection details for connection \"$connection\"");
    }

    /**
     * The driver isn't currently supported.
     *
     * @param string $connection The connection used.
     * @param string $driver     The driver used.
     * @return self
     */
    public static function unsupportedDriver($connection, $driver): self
    {
        return new self(
            "Connection \"$connection\" uses driver \"$driver\" which unfortunately isn\'t supported (yet!)"
        );
    }

    /**
     * The connection to use as default doesn't exist.
     *
     * @param string $connection The connection used.
     * @return self
     */
    public static function invalidDefaultConnection($connection): self
    {
        return new self(
            "The default connection \"$connection\" does not exist. "
            . "Please check the \$defaultConnection test-class property"
        );
    }

    /**
     * The connection to use as default doesn't exist.
     *
     * @param string $connection The connection used.
     * @return self
     */
    public static function invalidConnection($connection): self
    {
        return new self("The connection \"$connection\" does not exist.");
    }

    /**
     * The dest connection to remap doesn't exist.
     *
     * @param string  $connection The connection used.
     * @param boolean $isConfig   Did this error occur when looking at the config settings?
     *                            (it came from a test-class property otherwise).
     * @return self
     */
    public static function missingDestRemapConnection($connection, $isConfig): self
    {
        $errorPart = $isConfig
            ? 'Please review the "remap_connections" config setting'
            : 'Please review the $remapConnections test-class property';
        return new self("Cannot remap the connection \"$connection\" as it doesn't exist. $errorPart");
    }

    /**
     * The source connection to remap with doesn't exist.
     *
     * @param string  $connection The connection used.
     * @param boolean $isConfig   Did this error occur when looking at the config settings?
     *                            (it came from a test-class property otherwise).
     * @return self
     */
    public static function missingSrcRemapConnection($connection, $isConfig): self
    {
        $errorPart = $isConfig
            ? 'Please review the "remap_connections" config setting'
            : 'Please review the $remapConnections test-class property';
        return new self("Cannot remap using the connection \"$connection\" as it doesn't exist. $errorPart");
    }

    /**
     * The remap string couldn't be interpreted.
     *
     * @param string  $orig     The original remap string.
     * @param boolean $isConfig Did this error occur when looking at the config settings?
     *                          (it came from a test-class property otherwise).
     * @return self
     */
    public static function invalidConnectionRemapString($orig, $isConfig): self
    {
        $errorPart = $isConfig
            ? 'Please review the "remap_connections" config setting'
            : 'Please review the $remapConnections test-class property';
        return new self("Cannot interpret remap-database string \"$orig\". $errorPart");
    }
}
