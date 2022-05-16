<?php

namespace CodeDistortion\Adapt\Exceptions;

use Throwable;

/**
 * Exceptions generated when building a database.
 */
class AdaptBuildException extends AdaptException
{
    /**
     * The SQLite database name contains directory parts.
     *
     * @param string $databaseName The database that was to be used.
     * @return self
     */
    public static function SQLiteDatabaseNameContainsDirectoryParts(string $databaseName): self
    {
        return new self(
            "The SQLite database name \"$databaseName\" is invalid. "
            . "When using Adapt, please use only a filename (without a directory)"
        );
    }

    /**
     * The db user doesn't have permission to access the database.
     *
     * @param Throwable $previousException The original exception.
     * @return self
     */
    public static function accessDenied(Throwable $previousException): self
    {
        return new self(
            'Database access denied. TRY CONNECTING AS THE ROOT USER while testing. '
            . 'The user you connect with needs to have read + write access, '
            . 'as well as permission to create new databases',
            0,
            $previousException
        );
    }

    /**
     * Could not create the database.
     *
     * @param string    $databaseName      The database that was to be used.
     * @param Throwable $previousException The original exception.
     * @return self
     */
    public static function couldNotCreateDatabase(string $databaseName, Throwable $previousException): self
    {
        return new self("Failed to create database \"$databaseName\"", 0, $previousException);
    }

    /**
     * Could not reuse a database - it's owned by another project.
     *
     * @param string $databaseName The database that was to be used.
     * @param string $projectName  The current owner of the database.
     * @return self
     */
    public static function databaseOwnedByAnotherProject(string $databaseName, string $projectName): self
    {
        return new self(
            "Could not re-use database \"$databaseName\" as it is owned by another project \"$projectName\""
        );
    }

    /**
     * Could not run the migrations.
     *
     * @param Throwable $previousException The original exception.
     * @return self
     */
    public static function migrationsFailed(Throwable $previousException): self
    {
        return new self(
            "An error occurred when running the migrations: \"{$previousException->getMessage()}\"",
            0,
            $previousException
        );
    }

    /**
     * Could not run a seeder.
     *
     * @param string    $seeder            The seeder that was run.
     * @param Throwable $previousException The original exception.
     * @return self
     */
    public static function seederFailed(string $seeder, Throwable $previousException): self
    {
        return new self("Could not run the seeder \"$seeder\"", 0, $previousException);
    }

    /**
     * A database builder has been executed a second time.
     *
     * @return self
     */
    public static function databaseBuilderAlreadyExecuted(): self
    {
        return new self('This DatabaseBuilder has already been executed');
    }

    /**
     * The database being used isn't compatible with browser testing.
     *
     * @param string $driver The driver being used.
     * @return self
     */
    public static function databaseNotCompatibleWithBrowserTests(string $driver): self
    {
        return new self("$driver databases aren't compatible with browser tests");
    }
}
