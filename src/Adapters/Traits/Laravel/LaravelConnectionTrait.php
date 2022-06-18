<?php

namespace CodeDistortion\Adapt\Adapters\Traits\Laravel;

use CodeDistortion\Adapt\Support\PHPSupport;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
    protected function laravelMakeThisConnectionDefault()
    {
        config(['database.default' => $this->configDTO->connection]);

        $this->di->log->vDebug("Changed the default connection to: \"{$this->configDTO->connection}\"");
    }

    /**
     * Tell the adapter to use the given database name (the connection stays the same).
     *
     * @param string  $database     The name of the database to use.
     * @param boolean $applyLogging Enable or disable logging.
     * @return void
     */
    protected function laravelUseDatabase($database, $applyLogging)
    {
        $this->configDTO->database($database);

        $connection = $this->configDTO->connection;
        $changing = config("database.connections.$connection.database") != $database;

        if ($applyLogging) {
            $message = $changing
                ? "Changed the database for connection \"$connection\" to \"$database\""
                : "Leaving the database for connection \"$connection\" unchanged as \"$database\"";
            $this->di->log->vDebug($message);
        }

        if (!$changing) {
            return;
        }

        config(["database.connections.$connection.database" => $database]);

        $newConfig = config("database.connections.$connection");

        try {
            $connectionObj = DB::connection($connection);
            $connectionObj->setDatabaseName($database);
            $this->updateConnectionConfig($connectionObj, $newConfig);
        } catch (QueryException $exception) {
            // swallow these exceptions
            // depending on the version (of Laravel?), one of these are thrown when the
            // database server can't be connected to. e.g. this can happen when using SQLite
        } catch (InvalidArgumentException $exception) {
            // swallow these exceptions
            // depending on the version (of Laravel?), one of these are thrown when the
            // database server can't be connected to. e.g. this can happen when using SQLite
        }
    }

    /**
     * Force the connection object to use the update-to-date config settings (especially the database name).
     *
     * @param ConnectionInterface                   $connectionObj The connection object to update.
     * @param array<string, string|boolean|integer> $newConfig     The new config values to store.
     * @return void
     */
    private function updateConnectionConfig(ConnectionInterface $connectionObj, array $newConfig)
    {
        $oldConfig = $connectionObj->getConfig(null);
        $updatedConfig = array_merge($oldConfig, $newConfig);

        $unneededFields = array_diff(array_keys($newConfig), array_keys($oldConfig));
        foreach ($unneededFields as $field) {
            unset($updatedConfig[$field]);
        }

        PHPSupport::updatePrivateProperty($connectionObj, 'config', $updatedConfig);
    }

    /**
     * Get the database currently being used.
     *
     * @return string|null
     */
    protected function laravelGetCurrentDatabase()
    {
        $connection = $this->configDTO->connection;
        $return = config("database.connections.$connection.database");
        return is_string($return) ? $return : '';
    }
}
