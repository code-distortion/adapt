<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\LaravelSupport;
use Illuminate\Support\Facades\DB;
use stdClass;
use Throwable;

/**
 * Injectable class to abstract interaction with the database in Laravel.
 */
class LaravelDB
{
    /** @var string The database connection this object will use. */
    private $connection;


    /**
     * Specify the database connection to use.
     *
     * @param string $connection The connection to use.
     * @return static
     */
    public function useConnection($connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Get the current connection.
     *
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * Get the current connection's host (if relevant).
     *
     * @return ?string
     */
    public function getHost()
    {
        $connection = $this->connection ?? null;
        if (!$connection) {
            return null;
        }

        return LaravelSupport::configString("database.connections.$connection.host");
    }



    /**
     * Create a new PDO object, connected to the database server, but without selecting a database.
     *
     * @param string|null $database   The database to connect to (only when required by the driver - e.g. sqlite).
     * @param string|null $connection The connection to use (defaults to the current one).
     * @return LaravelPDO
     * @throws AdaptConfigException When the driver isn't recognised.
     */
    public function newPDO($database = null, $connection = null): LaravelPDO
    {
        $connection = $connection ?? $this->connection;

        $host = LaravelSupport::configString("database.connections.$connection.host");
        $port = LaravelSupport::configString("database.connections.$connection.port");
        $username = LaravelSupport::configString("database.connections.$connection.username");
        $password = LaravelSupport::configString("database.connections.$connection.password");
        $driver = LaravelSupport::configString("database.connections.$connection.driver");

        switch ($driver) {
            case 'mysql':
            case 'pgsql':
                $dsn = sprintf("$driver:host=%s;port=%d", $host, $port);
                break;
            case 'sqlite':
                $dsn = sprintf("$driver:%s", $database);
                break;
            default:
                throw AdaptConfigException::unsupportedDriver($connection, $driver);
        }

        return new LaravelPDO($dsn, $username, $password, []);
    }


    /**
     * Check if the given connection (+ database) exists.
     *
     * @return boolean
     */
    public function currentDatabaseExists(): bool
    {
        try {
            /** @var \Illuminate\Database\MySqlConnection $mysqlConnection */
            $mysqlConnection = DB::connection($this->connection);
            $mysqlConnection->getPdo();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }


    /**
     * Run a statement on the database.
     *
     * @param string  $query    The query to run.
     * @param mixed[] $bindings The values to bind.
     * @return boolean
     */
    public function statement($query, $bindings = []): bool
    {
        return DB::connection($this->connection)->statement($query, $bindings);
    }

    /**
     * Select from the database.
     *
     * @param string  $query    The query to run.
     * @param mixed[] $bindings The values to bind.
     * @return stdClass[]
     */
    public function select($query, $bindings = []): array
    {
        return DB::connection($this->connection)->select($query, $bindings);
    }

    /**
     * Insert into the database.
     *
     * @param string  $query    The query to run.
     * @param mixed[] $bindings The values to bind.
     * @return boolean
     */
    public function insert($query, $bindings = []): bool
    {
        return DB::connection($this->connection)->insert($query, $bindings);
    }

    /**
     * Update into the database.
     *
     * @param string  $query    The query to run.
     * @param mixed[] $bindings The values to bind.
     * @return boolean
     */
    public function update($query, $bindings = []): bool
    {
        return (bool) DB::connection($this->connection)->update($query, $bindings);
    }

    /**
     * Run a statement on the database using the PDO->exec(..) method directly.
     *
     * @param string $query The query to run.
     * @return boolean
     */
    public function directExec($query): bool
    {
        return DB::connection($this->connection)->getPDO()->exec($query);
    }

    /**
     * Disconnect from the database.
     *
     * @return void
     */
    public function purge()
    {
        DB::purge($this->connection);
    }
}
