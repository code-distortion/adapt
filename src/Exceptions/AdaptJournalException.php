<?php

namespace CodeDistortion\Adapt\Exceptions;

use Throwable;

/**
 * Exceptions generated relating to the journal re-use process.
 */
class AdaptJournalException extends AdaptException
{
    /**
     * Thrown when a table's CREATE TABLE query can't be updated with the new primary-key.
     *
     * @param string $table The name of the table the journal table is for.
     * @return self
     */
    public static function cannotReplaceCreateQueryKeys($table): self
    {
        return new self(
            "The journal table for table \"$table\" could not be created. "
            . "Its keys couldn't be updated with the new primary-key"
        );
    }
    /**
     * Thrown when a table's CREATE TABLE query can't be updated with the new journal table name and new fields.
     *
     * @param string $table The name of the table the journal table is for.
     * @return self
     */
    public static function cannotAlterCreateQueryTableName($table): self
    {
        return new self(
            "The journal table for table \"$table\" could not be created. "
            . "The name couldn't be replaced and new fields added"
        );
    }

    /**
     * Thrown when a table's journal table could not be created - because the CREATE TABLE query failed.
     *
     * @param string         $table             The name of the table the journal table is for.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function journalCreateTableQueryFailed($table, $previousException = null): self
    {
        $message = "The journal table for table \"$table\" could not be created. The CREATE TABLE query failed";

        return $previousException
            ? new self($message, 0, $previousException)
            : new self($message);
    }

    /**
     * Thrown when a table's journal table could not be created - for an unknown reason.
     *
     * @param string         $table             The name of the table the journal table is for.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function cannotCreateJournalTable($table, $previousException = null): self
    {
        $message = "The journal table for table \"$table\" could not be created. Something went wrong";

        return $previousException
            ? new self($message, 0, $previousException)
            : new self($message);
    }



    /**
     * Thrown when a table's journal trigger could not be created.
     *
     * @param string         $table             The name of the table the trigger table is for.
     * @param Throwable|null $previousException The original exception.
     * @return self
     */
    public static function cannotCreateJournalTrigger($table, $previousException = null): self
    {
        $message = "A trigger for the table \"$table\" could not be created";
        return $previousException
            ? new self($message, 0, $previousException)
            : new self($message);
    }



    /**
     * A table doesn't have a primary-key.
     *
     * @param string $table The table being updated when the error occurred.
     * @return self
     */
    public static function tableHasNoPrimaryKey($table): self
    {
        return new self("The \"$table\" table doesn't have a primary-key (or unique index)");
    }



    /**
     * An error occurred when using the journal to undo changes.
     *
     * @param string    $table             The table being updated when the error occurred.
     * @param Throwable $previousException The original exception.
     * @return self
     */
    public static function couldNotReverseJournal($table, $previousException): self
    {
        $message = "An error occurred while using the journal to reverse changes to table \"$table\"";
        return new self($message, 0, $previousException);
    }
}
