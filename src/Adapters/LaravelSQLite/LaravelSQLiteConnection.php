<?php

namespace CodeDistortion\Adapt\Adapters\LaravelSQLite;

use CodeDistortion\Adapt\Adapters\Interfaces\ConnectionInterface;
use CodeDistortion\Adapt\Adapters\Traits\InjectTrait;
use CodeDistortion\Adapt\Adapters\Traits\Laravel\LaravelConnectionTrait;

/**
 * Database-adapter methods related to managing a Laravel/SQLite database connection.
 */
class LaravelSQLiteConnection implements ConnectionInterface
{
    use InjectTrait;
    use LaravelConnectionTrait;


    /**
     * Set the this builder's database connection as the default one.
     *
     * @return void
     */
    public function makeThisConnectionDefault()
    {
        $this->laravelMakeThisConnectionDefault();
    }

    /**
     * Tell the adapter to use the given database name.
     *
     * @param string $database The name of the database to use.
     * @return void
     */
    public function useDatabase($database)
    {
        $this->laravelUseDatabase($database);
    }
}
