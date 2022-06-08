<?php

namespace CodeDistortion\Adapt\Adapters\LaravelMySQL;

use CodeDistortion\Adapt\Adapters\AbstractClasses\AbstractFind;
use CodeDistortion\Adapt\Adapters\Interfaces\FindInterface;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Database-adapter methods related to finding Laravel/MySQL databases.
 */
class LaravelMySQLFind extends AbstractFind implements FindInterface
{
    /**
     * Generate the list of existing databases.
     *
     * @return string[]
     */
    protected function listDatabases(): array
    {
        return $this->di->db->newPDO()->listDatabases();
    }

    /**
     * Check if this database should be ignored.
     *
     * @param string $database The database to check.
     * @return boolean
     */
    protected function shouldIgnoreDatabase(string $database): bool
    {
        // ignore MySQL's default databases
        return in_array($database, ['information_schema', 'mysql', 'performance_schema', 'sys']);
    }

    /**
     * Build DatabaseMetaInfo objects for a database.
     *
     * @param string $database  The database to use.
     * @param string $buildHash The current build-hash.
     * @return DatabaseMetaInfo|null
     */
    protected function buildDatabaseMetaInfo(string $database, string $buildHash): ?DatabaseMetaInfo
    {
        $pdo = $this->di->db->newPDO($database);
        return $this->buildDatabaseMetaInfoX(
            $this->di->db->getConnection(),
            $database,
            $pdo->fetchReuseTableInfo("SELECT * FROM `$database`.`" . Settings::REUSE_TABLE . "` LIMIT 0, 1"),
            $buildHash
        );
    }

    /**
     * Remove the given database.
     *
     * @param DatabaseMetaInfo $databaseMetaInfo The info object representing the database.
     * @return boolean
     */
    protected function removeDatabase(DatabaseMetaInfo $databaseMetaInfo): bool
    {
        $logTimer = $this->di->log->newTimer();

        $pdo = $this->di->db->newPDO(null, $databaseMetaInfo->connection);
        $pdo->dropDatabase("DROP DATABASE IF EXISTS `$databaseMetaInfo->name`", $databaseMetaInfo->name);

        $stale = (!$databaseMetaInfo->isValid ? ' stale' : '');
        $driver = $databaseMetaInfo->driver;
        $this->di->log->debug("Removed$stale $driver database: \"$databaseMetaInfo->name\"", $logTimer);

        return true;
    }

    /**
     * Get the database's size in bytes.
     *
     * @param string $database The database to get the size of.
     * @return integer|null
     */
    protected function size(string $database): ?int
    {
        $pdo = $this->di->db->newPDO();
        $size = $pdo->size(
            "SELECT SUM(DATA_LENGTH + INDEX_LENGTH) AS size "
            . "FROM INFORMATION_SCHEMA.TABLES "
            . "WHERE TABLE_SCHEMA = '$database'"
        );
        return is_integer($size) ? $size :  null;
    }
}
