<?php

namespace CodeDistortion\Adapt\Adapters\LaravelPostgreSQL;

use CodeDistortion\Adapt\Adapters\AbstractClasses\AbstractFind;
use CodeDistortion\Adapt\Adapters\Interfaces\FindInterface;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\Support\Settings;
use Throwable;

/**
 * Database-adapter methods related to finding Laravel/PostgreSQL databases.
 */
class LaravelPostgreSQLFind extends AbstractFind implements FindInterface
{
    /**
     * Look for databases and build DatabaseMetaInfo objects for them.
     *
     * Only pick databases that have "reuse" meta-info stored.
     *
     * @param string|null $origDBName The original database that this instance is for - will be ignored when null.
     * @param string|null $buildHash  The current build-hash.
     * @return DatabaseMetaInfo[]
     */
    public function findDatabases(?string $origDBName, ?string $buildHash): array
    {
        $databaseMetaInfos = [];
        $pdo = $this->di->db->newPDO();
        $databases = $pdo->listDatabases();
        foreach ($databases as $database) {

            try {
                $table = Settings::REUSE_TABLE;
                $pdo = $this->di->db->newPDO($database);

                $databaseMetaInfos[] = $this->buildDatabaseMetaInfo(
                    $this->di->db->getConnection(),
                    $database,
                    $pdo->fetchReuseTableInfo("SELECT * FROM \"$table\" LIMIT 1 OFFSET 0"),
                    $buildHash
                );
            } catch (Throwable $e) {
            }
        }
        return array_values(array_filter($databaseMetaInfos));
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
        $pdo->dropDatabase("DROP DATABASE IF EXISTS \"$databaseMetaInfo->name\"", $databaseMetaInfo->name);

        $this->di->log->debug(
            'Removed ' . (!$databaseMetaInfo->isValid ? 'old ' : '') . "database: \"$databaseMetaInfo->name\"",
            $logTimer
        );
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
        $size = $pdo->size("SELECT pg_database_size('$database')");
        return is_integer($size) ? $size :  null;
    }
}
