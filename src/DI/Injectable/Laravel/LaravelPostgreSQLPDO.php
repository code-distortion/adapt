<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

/**
 * Injectable class to abstract interaction with the database in Laravel.
 */
class LaravelPostgreSQLPDO extends AbstractLaravelPDO
{
    /**
     * Return the list of existing databases.
     *
     * @return string[]
     */
    public function listDatabases(): array
    {
        return $this->performListDatabases("SELECT \"datname\" FROM \"pg_database\"", 'datname');
    }
}
