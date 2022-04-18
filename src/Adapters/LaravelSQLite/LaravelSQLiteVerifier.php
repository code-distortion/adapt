<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\VerifierInterface;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLVerifier;

/**
 * Database-adapter methods related to verifying a Laravel/SQLite database's structure and content.
 */
class LaravelSQLiteVerifier extends LaravelMySQLVerifier implements VerifierInterface
{
    /**
     * Determine whether this database can be verified or not (for checking of database structure and content).
     *
     * @return boolean
     */
    public function isVerifiable(): bool
    {
        return false;
    }
}
