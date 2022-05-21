<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

/**
 * Injectable class to abstract interaction with the database in Laravel.
 */
class LaravelMySQLPDO extends AbstractLaravelPDO
{
    /**
     * Return the list of existing databases.
     *
     * @return string[]
     */
    public function listDatabases(): array
    {
        return $this->performListDatabases("SHOW DATABASES", 'Database');
    }
}
