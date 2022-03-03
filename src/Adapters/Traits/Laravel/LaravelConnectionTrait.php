<?php

namespace CodeDistortion\Adapt\Adapters\Traits\Laravel;

/**
 * Database-adapter methods related to managing a Laravel database connection.
 */
trait LaravelConnectionTrait
{
    /**
     * Set this builder's database connection as the default one.
     *
     * @return void
     */
    protected function laravelMakeThisConnectionDefault(): void
    {
        config(['database.default' => $this->config->connection]);

        $this->di->log->debug("Changed the default connection to: \"{$this->config->connection}\"");
    }

    /**
     * Tell the adapter to use the given database name (the connection stays the same).
     *
     * @param string  $database     The name of the database to use.
     * @param boolean $applyLogging Enable or disable logging.
     * @return void
     */
    protected function laravelUseDatabase(string $database, bool $applyLogging): void
    {
        $this->config->database($database);

        $connection = $this->config->connection;

        if ($applyLogging) {

            $message = config("database.connections.$connection.database") == $database
                ? "Using connection \"$connection\"'s original database \"$database\""
                : "Changed the database for connection \"$connection\" to \"$database\"";

            $this->di->log->debug($message);
        }

        config(["database.connections.$connection.database" => $database]);
    }

    /**
     * Get the database currently being used.
     *
     * @return string|null
     */
    protected function laravelGetCurrentDatabase(): ?string
    {
        $connection = $this->config->connection;

        return config("database.connections.$connection.database");
    }
}
