<?php

namespace CodeDistortion\Adapt\Exceptions;

use Throwable;

/**
 * Exceptions generated when building a database.
 */
class AdaptBuildException extends AdaptException
{
    /**
     * The db user doesn't have permission to access the database.
     *
     * @param Throwable $originalException The originally thrown exception.
     * @return self
     */
    public static function accessDenied(Throwable $originalException)
    {
        return new self('Database access denied. TRY CONNECTING AS THE ROOT USER while testing. '
        . 'The user you connect with needs to have read + write access, '
        . 'as well as permission to create new databases', 0, $originalException);
    }

    /**
     * Could not reuse a database - it's owned by another project.
     *
     * @param string $databaseName The database that was to be used.
     * @param string $projectName  The current owner of the database.
     * @return self
     */
    public static function databaseOwnedByAnotherProject(string $databaseName, string $projectName)
    {
        return new self('Could not re-use database "' . $databaseName . '" as it is owned by project "' . $projectName . '"');
    }

    /**
     * Could not run a seeder.
     *
     * @param string    $seeder            The seeder that was run.
     * @param Throwable $originalException The originally thrown exception.
     * @return self
     */
    public static function seederFailed(string $seeder, Throwable $originalException)
    {
        return new self('Could not run seeder "' . $seeder . '"', 0, $originalException);
    }

    /**
     * A database builder has been executed a second time.
     *
     * @return self
     */
    public static function databaseBuilderAlreadyExecuted()
    {
        return new self('This DatabaseBuilder has already been executed');
    }
}
