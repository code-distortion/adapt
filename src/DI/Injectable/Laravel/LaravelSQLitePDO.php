<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

/**
 * Injectable class to abstract interaction with the database in Laravel.
 */
class LaravelSQLitePDO extends AbstractLaravelPDO
{
    /**
     * Return the list of existing databases.
     *
     * @return string[]
     */
    public function listDatabases(): array
    {
        return []; // i.e. PDO isn't used to find SQLite databases
    }
}
