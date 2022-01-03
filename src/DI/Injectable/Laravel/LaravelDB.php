<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use Illuminate\Support\Facades\DB;
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
     * Create a new PDO object, connected to the database server, but without selecting a database.
     *
     * @param string|null $database   The database to connect to (only when required by the driver - eg. sqlite).
     * @param string|null $connection The connection to use (defaults to the current one).
     * @return LaravelPDO
     * @throws AdaptConfigException Thrown when the driver isn't recognised.
     */
    public function newPDO($database = null, $connection = null): LaravelPDO
    {
        $connection = $connection ?? $this->connection;

        $host = config("database.connections.$connection.host");
        $port = config("database.connections.$connection.port");
        $username = config("database.connections.$connection.username");
        $password = config("database.connections.$connection.password");

        $driver = config("database.connections.$connection.driver");
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
            DB::connection($this->connection)->getPdo();
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
     * @return mixed[]
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
        return DB::connection($this->connection)->update($query, $bindings);
    }

    /**
     * Drop all the tables from the current database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        // @todo make this works for database types other than mysql
        // @todo make sure this works with views
//        if (mysql) { ...
        $tables = [];
        foreach (DB::connection($this->connection)->select("SHOW TABLES") as $row) {
            $tables[] = array_values(get_object_vars($row))[0];
        }

        DB::connection($this->connection)->statement("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($tables as $table) {
            DB::connection($this->connection)->statement("DROP TABLE `" . $table . "`");
        }
        DB::connection($this->connection)->statement("SET FOREIGN_KEY_CHECKS = 1");
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
