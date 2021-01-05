<?php

namespace CodeDistortion\Adapt\DI\Injectable;

use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use DB;
use Throwable;

/**
 * Injectable class to abstract interaction with the database in Laravel.
 */
class LaravelDB
{
    /**
     * The database connection this object will use.
     *
     * @var string
     */
    private string $connection;


    /**
     * Specify the database connection to use.
     *
     * @param string $connection The connection to use.
     * @return static
     */
    public function useConnection(string $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Create a new PDO object, connected to the database server, but without selecting a database.
     *
     * @param string|null $database The database to connect to (only when required by the driver - eg. sqlite).
     * @return LaravelPDO
     * @throws AdaptConfigException Thrown when the driver isn't recognised.
     */
    public function newPDO(string $database = null): LaravelPDO
    {
        $host = config("database.connections.$this->connection.host");
        $port = config("database.connections.$this->connection.port");
        $username = config("database.connections.$this->connection.username");
        $password = config("database.connections.$this->connection.password");

        $driver = config("database.connections.$this->connection.driver");
        switch ($driver) {
            case 'mysql':
                $dsn = sprintf('mysql:host=%s;port=%d', $host, $port);
                break;
            case 'sqlite':
                $dsn = sprintf('sqlite:%s', $database);
                break;
            default:
                throw AdaptConfigException::unsupportedDriver($this->connection, $driver);
        }

        return new LaravelPDO(
            $dsn,
            $username,
            $password,
            []
        );
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
    public function statement(string $query, array $bindings = []): bool
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
    public function select(string $query, array $bindings = []): array
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
    public function insert(string $query, array $bindings = []): bool
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
    public function update(string $query, array $bindings = []): bool
    {
        return DB::connection($this->connection)->update($query, $bindings);
    }

    /**
     * Drop all the tables from the current database.
     *
     * @return void
     */
    public function dropAllTables(): void
    {
        // @todo make this work for database types other than mysql
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
    public function purge(): void
    {
        DB::purge($this->connection);
    }
}
