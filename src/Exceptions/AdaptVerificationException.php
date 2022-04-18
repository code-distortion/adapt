<?php

namespace CodeDistortion\Adapt\Exceptions;

/**
 * Exceptions generated when verifying that a database's structure and content hasn't changed.
 */
class AdaptVerificationException extends AdaptException
{
    /**
     * A table has been created.
     *
     * @param string $database The database the table is from.
     * @param string $table    The table that the problem was found in.
     * @return self
     */
    public static function tableWasCreated($database, $table): self
    {
        return new self("Table \"$database.$table\" was created during the test");
    }

    /**
     * A table has been removed.
     *
     * @param string $database The database the table is from.
     * @param string $table    The table that the problem was found in.
     * @return self
     */
    public static function tableWasRemoved($database, $table): self
    {
        return new self("Table \"$database.$table\" was removed during the test");
    }

    /**
     * A table's structure has changed.
     *
     * @param string $database The database the table is from.
     * @param string $table    The table that the problem was found in.
     * @return self
     */
    public static function tableStructureHasChanged($database, $table): self
    {
        return new self("The structure of table \"$database.$table\" changed during the test");
    }

    /**
     * A table's content has changed.
     *
     * @param string $database The database the table is from.
     * @param string $table    The table that the problem was found in.
     * @return self
     */
    public static function tableContentHasChanged($database, $table): self
    {
        return new self("The data in table \"$database.$table\" changed during the test");
    }
}
