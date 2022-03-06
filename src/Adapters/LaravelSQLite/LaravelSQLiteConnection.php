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
     * Set this builder's database connection as the default one.
     *
     * @return void
     */
    public function makeThisConnectionDefault(): void
    {
        $this->laravelMakeThisConnectionDefault();
    }

    /**
     * Tell the adapter to use the given database name.
     *
     * @param string  $database     The name of the database to use.
     * @param boolean $applyLogging Enable or disable logging.
     * @return void
     */
    public function useDatabase(string $database, bool $applyLogging = true): void
    {
        $this->laravelUseDatabase($database, $applyLogging);
    }

    /**
     * Get the database currently being used.
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->laravelGetCurrentDatabase();
    }
}
