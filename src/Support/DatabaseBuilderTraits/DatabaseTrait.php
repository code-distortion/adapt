<?php

namespace CodeDistortion\Adapt\Support\DatabaseBuilderTraits;

use CodeDistortion\Adapt\DatabaseBuilder;

/**
 * @mixin DatabaseBuilder
 */
trait DatabaseTrait
{
    /**
     * Use the desired database.
     *
     * @return void
     */
    private function pickDatabaseNameAndUse()
    {
        if ($this->configDTO->shouldBuildRemotely()) {
            return;
        }

        $this->useDatabase($this->pickDatabaseName());
    }

    /**
     * Choose the name of the database to use.
     *
     * @return string
     */
    private function pickDatabaseName(): string
    {
        $dbNameChecksumPart = $this->configDTO->usingScenarios()
            ? $this->generateDatabaseNameChecksumPart()
            : null;

        return $this->dbAdapter()->name->generateDBName($this->configDTO->usingScenarios(), $dbNameChecksumPart);
    }

    /**
     * Generate a checksum to use in the database name.
     *
     * Based on the source-files checksum, extended-scenario checksum.
     *
     * @return string
     */
    private function generateDatabaseNameChecksumPart(): string
    {
        return $this->hasher->generateDatabaseNameChecksumPart(
            $this->configDTO->pickSeedersToInclude(),
            $this->configDTO->databaseModifier
        );
    }

    /**
     * Use the desired database.
     *
     * @param string $name The name of the database to use.
     * @return void
     */
    private function useDatabase(string $name)
    {
        $this->dbAdapter()->connection->useDatabase($name);
    }

    /**
     * Use the desired database - with no logging.
     *
     * @param string $name The name of the database to use.
     * @return void
     */
    private function silentlyUseDatabase(string $name)
    {
        $this->dbAdapter()->connection->useDatabase($name, false);
    }

    /**
     * Get the database currently being used.
     *
     * @return string|null
     */
    private function getCurrentDatabase()
    {
        return $this->dbAdapter()->connection->getDatabase();
    }

    /**
     * Retrieve the current connection's original database name.
     *
     * @return string
     */
    private function origDBName(): string
    {
        return $this->configDTO->origDatabase;
    }
}
