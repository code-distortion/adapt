<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

use CodeDistortion\Adapt\Exceptions\AdaptBuildException;
use PDO;
use stdClass;
use Throwable;

/**
 * Injectable class to abstract interaction with the database in Laravel.
 */
abstract class AbstractLaravelPDO
{
    /** @var PDO|null The pdo connection to use. */
    protected PDO|null $pdo;


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
        ?string $username,
        ?string $password,
        array $connectOptions
    ) {
        $this->pdo = new PDO($dsn, $username, $password, $connectOptions);
    }


    /**
     * Run a select query.
     *
     * @param string $query The query to run.
     * @return array|null
     */
    public function select(string $query): ?array
    {
        $pdoStatement = $this->pdo->query($query);
        if (!$pdoStatement) {
            return null;
        }

        return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new database.
     *
     * @param string $createQuery The query to run to create the database.
     * @return void
     */
    public function createDatabase(string $createQuery): void
    {
        $this->pdo->exec($createQuery);
    }

    /**
     * Drop a database.
     *
     * @param string $dropQuery    The query to run to remove the database.
     * @param string $databaseName The name of the database being dropped.
     * @return void
     * @throws AdaptBuildException When the database couldn't be dropped.
     */
    public function dropDatabase(string $dropQuery, string $databaseName): void
    {
        try {
            $this->pdo->exec($dropQuery);
        } catch (Throwable $e) {
            throw AdaptBuildException::couldNotDropDatabase($databaseName, $e);
        }
    }

    /**
     * Return the list of existing databases.
     *
     * @return string[]
     */
    abstract public function listDatabases(): array;

    /**
     * Return the list of existing databases.
     *
     * @param string $query The query to run to get the list.
     * @param string $field The field to get from the results.
     * @return string[]
     */
    protected function performListDatabases(string $query, string $field): array
    {
        try {

            $pdoStatement = $this->pdo->query($query);
            if (!$pdoStatement) {
                return [];
            }

            $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                return [];
            }

            $databases = [];
            foreach ($rows as $row) {
                $databases[] = $row[$field];
            }
            return $databases;

        } catch (Throwable) {
        }
        return [];
    }

    /**
     * Return the reuse-info (if present) from a database.
     *
     * @param string $query The query to run to fetch the reuse-data.
     * @return stdClass|null
     */
    public function fetchReuseTableInfo(string $query): ?stdClass
    {
        try {

            $pdoStatement = $this->pdo->query($query);
            if (!$pdoStatement) {
                return null;
            }

            $rows = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                return null;
            }

            $row = reset($rows);
            return ($row ? (object) $row : null);

        } catch (Throwable) {
        }
        return null;
    }

    /**
     * Get the size of the database in bytes.
     *
     * @param string $getSizeQuery The query to run to determine the database size.
     * @return integer|boolean
     */
    public function size(string $getSizeQuery)
    {
        try {

            $pdoStatement = $this->pdo->query($getSizeQuery);
            if (!$pdoStatement) {
                return false;
            }

            $row = $pdoStatement->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return false;
            }

            return (int) array_shift($row);

        } catch (Throwable) {
        }
        return false;
    }
}
