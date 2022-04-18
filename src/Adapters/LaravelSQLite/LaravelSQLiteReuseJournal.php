<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\ReuseJournalInterface;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLReuseJournal;

/**
 * Database-adapter methods related to managing Laravel/SQLite reuse through journaling.
 */
class LaravelSQLiteReuseJournal extends LaravelMySQLReuseJournal implements ReuseJournalInterface
{
    /**
     * Determine if a journal can be used on this database (for database re-use).
     *
     * @return boolean
     */
    public function isJournalable(): bool
    {
        return false;
    }
}
