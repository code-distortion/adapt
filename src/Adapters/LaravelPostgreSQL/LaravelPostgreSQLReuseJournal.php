<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\Interfaces\ReuseJournalInterface;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLReuseJournal;

/**
 * Database-adapter methods related to managing Laravel/PostgreSQL reuse through journaling.
 */
class LaravelPostgreSQLReuseJournal extends LaravelMySQLReuseJournal implements ReuseJournalInterface
{
    /**
     * Determine if a journal can be used on this database (for database re-use).
     *
     * @return boolean
     */
    public function supportsJournaling(): bool
    {
        return false;
    }
}
