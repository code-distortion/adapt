<?php

namespace CodeDistortion\Adapt\Adapters\Traits\Laravel;

/**
 * Database-adapter methods related to managing a Laravel database connection.
 */
trait LaravelConnectionTrait
{
    /**
     * Set the this builder's database connection as the default one.
     *
     * @return void
     */
    protected function laravelMakeThisConnectionDefault(): void
    {
        config(['database.default' => $this->config->connection]);

        $this->di->log->debug('Changed the default connection to: "' . $this->config->connection . '"');
    }

    /**
     * Tell the adapter to use the given database name (the connection stays the same).
     *
     * @param string $database The name of the database to use.
     * @return void
     */
    protected function laravelUseDatabase(string $database): void
    {
        $this->config->database($database);

        $connection = $this->config->connection;
        if (config("database.connections.$connection.database") != $database) {
            config(["database.connections.$connection.database" => $database]);
            $this->di->log->debug('Changed the database for connection "' . $connection . '" to "' . $database . '"');
        } else {
            $this->di->log->debug('Using connection "' . $connection . '"\'s original database "' . $database . '"');
        }
    }
}
