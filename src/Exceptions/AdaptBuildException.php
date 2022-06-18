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
    public static function SQLiteDatabaseNameContainsDirectoryParts($databaseName): self
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
    public static function accessDenied($previousException): self
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
     * Thrown when a MySQL database name is too long.
     *
     * @param string $database The name of the database.
     * @return self
     */
    public static function yourMySQLDatabaseNameIsTooLongCouldYouChangeItThx($database): self
    {
        return new self(
            "The database name \"$database\" is longer than MySQL's limit (64 characters). "
            . "Please use a shorter database name"
        );
    }

    /**
     * Thrown when a PostgreSQL database name is too long.
     *
     * @param string $database The name of the database.
     * @return self
     */
    public static function yourPostgreSQLDatabaseNameIsTooLongCouldYouChangeItThx($database): self
    {
        return new self(
            "The database name \"$database\" is longer than PostgreSQL's limit (63 characters). "
            . "Please use a shorter database name"
        );
    }

    /**
     * Could not drop the database.
     *
     * @param string         $databaseName      The database that was to be used.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function couldNotDropDatabase($databaseName, $previousException = null): self
    {
        return $previousException
            ? new self("Could not drop database \"$databaseName\"", 0, $previousException)
            : new self("Could not drop database \"$databaseName\"");
    }

    /**
     * Could not create the database.
     *
     * @param string    $databaseName      The database that was to be used.
     * @param string    $driver            The database driver being used.
     * @param Throwable $previousException The original exception.
     * @return self
     */
    public static function couldNotCreateDatabase(
        $databaseName,
        $driver,
        $previousException
    ): self {

        return new self("Failed to create $driver database \"$databaseName\"", 0, $previousException);
    }

    /**
     * Could not reuse a database - it's owned by another project.
     *
     * @param string $databaseName The database that was to be used.
     * @param string $projectName  The current owner of the database.
     * @return self
     */
    public static function databaseOwnedByAnotherProject($databaseName, $projectName): self
    {
        return new self(
            "Could not re-use database \"$databaseName\" as it is owned by another project \"$projectName\""
        );
    }

    /**
     * Could not run the migrations.
     *
     * @param string|null $migrationsPath    The path the migrations are in.
     * @param Throwable   $previousException The original exception.
     * @return self
     */
    public static function migrationsFailed($migrationsPath, $previousException): self
    {
        $message = $migrationsPath
            ? "An error occurred when running the migrations \"$migrationsPath\""
            : "An error occurred when running the migrations";

        return new self($message, 0, $previousException);
    }

    /**
     * Could not run a seeder.
     *
     * @param string    $seeder            The seeder that was run.
     * @param Throwable $previousException The original exception.
     * @return self
     */
    public static function seederFailed($seeder, $previousException): self
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
    public static function databaseNotCompatibleWithBrowserTests($driver): self
    {
        return new self("$driver databases aren't compatible with browser tests");
    }
}
