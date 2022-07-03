<?php

namespace CodeDistortion\Adapt\Exceptions;

use CodeDistortion\Adapt\Support\Settings;
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
        return new self("The " . Settings::LARAVEL_ENV_TESTING_FILE . " file could not be loaded");
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
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function cannotCreateStorageDir($storageDir, $previousException = null): self
    {
        $message = "Could not create the storage directory \"$storageDir\". Please review the \"storage_dir\" setting";
        return $previousException
            ? new self($message, 0, $previousException)
            : new self($message);
    }

    /**
     * A .gitignore file could not be created.
     *
     * @param string $path The location of the .gitignore file.
     * @return self
     */
    public static function cannotCreateGitIgnoreFile($path): self
    {
        return new self("Could not create the .gitignore file \"$path\". Please review the \"storage_dir\" setting");
    }

    /**
     * An initial-import path could not be read.
     *
     * @param string $path The invalid path.
     * @return self
     */
    public static function initialImportPathInvalid($path): self
    {
        return new self(
            "Couldn't open initial-import file \"$path\". "
            . "Please review the \"build_sources.initial_imports\" config setting"
        );
    }

    /**
     * The migrations' path could not be read.
     *
     * @param string $path The invalid path.
     * @return self
     */
    public static function migrationsPathInvalid($path): self
    {
        return new self(
            "The migrations directory \"$path\" does not exist. "
            . "Please review the \"build_sources.migrations\" config setting, or \$migrations test-class property"
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
            "Couldn't open file or directory \"$path\". "
            . "Please review the \"cache_invalidation.locations\" config setting"
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
            . "Please review the \"cache_invalidation.locations\" config setting. "
            . "Note: Laravel renamed the seeders directory from \"database/seeds\" to \"database/seeders\" in Laravel 8"
        );
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
            "Connection \"$connection\" uses driver \"$driver\" which unfortunately isn't supported by Adapt"
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
            . "Please check the \"default_connection\" config setting, or \$defaultConnection test-class property"
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
            ? 'Please check the "remap_connections" config setting'
            : 'Please check the $remapConnections test-class property';
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
            ? 'Please check the "remap_connections" config setting'
            : 'Please check the $remapConnections test-class property';
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
            ? 'Please check the "remap_connections" config setting'
            : 'Please check the $remapConnections test-class property';
        return new self("Cannot interpret remap-database string \"$orig\". $errorPart");
    }

    /**
     * When a connection would be prepared by more than one DatabaseBuilder.
     *
     * @param string $connection The duplicated connection.
     * @return self
     */
    public static function sameConnectionBeingBuiltTwice($connection): self
    {
        return new self("The \"$connection\" connection is being prepared more than once");
    }

    /**
     * When more than one DatabaseBuilder has been specified as being the "default".
     *
     * @return self
     */
    public static function tooManyDefaultConnections(): self
    {
        return new self("Only one connection can be specified as being the default connection");
    }
}
