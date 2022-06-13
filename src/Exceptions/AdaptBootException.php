<?php

namespace CodeDistortion\Adapt\Exceptions;

/**
 * Exceptions generated when booting a builder.
 */
class AdaptBootException extends AdaptException
{
    /**
     * Thrown when Laravel's RefreshDatabase, DatabaseTransactions or DatabaseMigrations traits are detected.
     *
     * @param string $trait The detected trait.
     * @return self
     */
    public static function laravelDatabaseTraitDetected(string $trait): self
    {
        $temp = (array) preg_split('/[\\\\\/]+/', $trait);
        $trait = array_pop($temp);

        return new self(
            "Laravel's \"$trait\" trait was detected. Please remove it from your tests when using Adapt"
        );
    }

    /**
     * Thrown when the --recreate-databases option has been added when --parallel testing.
     *
     * Because Adapt dynamically decides which database/s to use based on the settings for each test, it's not
     * practical to pre-determine which ones to rebuild. And because of the nature of parallel testing, it's also not
     * possible to simply remove oll of the databases before running the tests.
     *
     * @return self
     */
    public static function parallelTestingSaysRebuildDBs(): self
    {
        return new self('Instead of using --recreate-databases, please use "php artisan adapt:remove (--force)"');
    }

    /**
     * The ConfigDTO couldn't be built from its payload.
     *
     * @return self
     */
    public static function couldNotReadRemoteConfiguration(): self
    {
        return new self("Could not read the remote configuration details");
    }

    /**
     * The database name must be a string with characters in it.
     *
     * @param string $database The database name.
     * @return self
     */
    public static function databaseNameIsInvalid(string $database): self
    {
        return new self("The Database name \"$database\" is invalid");
    }
}
