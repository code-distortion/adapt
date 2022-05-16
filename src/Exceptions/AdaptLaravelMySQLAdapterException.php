<?php

namespace CodeDistortion\Adapt\Exceptions;

/**
 * Exceptions generated by the LaravelMySQLAdapter.
 */
class AdaptLaravelMySQLAdapterException extends AdaptException
{
    /**
     * Thrown when a database name is to long.
     *
     * @param string $database The name of the database.
     * @return self
     */
    public static function yourDatabaseNameIsTooLongCouldYouChangeItThx($database): self
    {
        return new self(
            "The database name \"$database\" is longer than MySQL's limit (64 characters). "
            . "Please use a shorter database name"
        );
    }
}
