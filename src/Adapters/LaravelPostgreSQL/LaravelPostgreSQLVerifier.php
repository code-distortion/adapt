<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\Interfaces\VerifierInterface;
use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLVerifier;

/**
 * Database-adapter methods related to verifying a Laravel/PostgreSQL database's structure and content.
 */
class LaravelPostgreSQLVerifier extends LaravelMySQLVerifier implements VerifierInterface
{
    /**
     * Determine whether this database can be verified or not (for checking of database structure and content).
     *
     * @return boolean
     */
    public function supportsVerification(): bool
    {
        return false;
    }
}
