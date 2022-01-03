<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

use PDO;
use stdClass;
use Throwable;

/**
 * Injectable class to abstract interaction with the database in Laravel.
 */
class LaravelPDO
{
    /** @var PDO The pdo connection to use. */
    private $pdo;


    /**
     * Constructor.
     *
     * @param string      $dsn            The dsn to use when connecting directly to the database.
     * @param string|null $username       The username to use when connecting directly to the database.
     * @param string|null $password       The password to use when connecting directly to the database.
     * @param mixed[]     $connectOptions The connection-options to use when connecting directly to the database.
     */
    public function __construct(
        string $dsn,
        $username,
        $password,
        array $connectOptions
    ) {
        $this->pdo = new PDO($dsn, $username, $password, $connectOptions);
    }


    /**
     * Create a new database.
     *
     * @param string $createQuery The query to run to create the database.
     * @return boolean
     */
    public function createDatabase($createQuery): bool
    {
        try {
            $this->pdo->exec($createQuery);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Drop a database.
     *
     * @param string $dropQuery The query to run to remove the database.
     * @return boolean
     */
    public function dropDatabase($dropQuery): bool
    {
        try {
            $this->pdo->exec($dropQuery);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Return the list of existing databases.
     *
     * @return string[]
     */
    public function listDatabases(): array
    {
        try {
            $pdoStatement = $this->pdo->query("SHOW DATABASES");
            if ($pdoStatement) {
                $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    $databases = [];
                    foreach ($rows as $row) {
                        $databases[] = $row['Database'];
                    }
                    return $databases;
                }
            }
        } catch (Throwable $e) {
        }
        return [];
    }

    /**
     * Return the reuse-info (if present) from a database.
     *
     * @param string $query The query to run to fetch the reuse-data.
     * @return stdClass|null
     */
    public function fetchReuseTableInfo($query)
    {
        try {
            $pdoStatement = $this->pdo->query($query);
            if ($pdoStatement) {
                $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    $row = reset($rows);
                    return ($row ? (object) $row : null);
                }
            }
        } catch (Throwable $e) {
        }
        return null;
    }

    /**
     * Get the size of the database in bytes.
     *
     * @param string $getSizeQuery The query to run to determine the database size.
     * @return integer|boolean
     */
    public function size($getSizeQuery)
    {
        try {
            $pdoStatement = $this->pdo->query($getSizeQuery);
            if ($pdoStatement) {
                $row = $pdoStatement->fetch(PDO::FETCH_ASSOC);
                return (int) array_shift($row);
            }
        } catch (Throwable $e) {
        }
        return false;
    }
}
