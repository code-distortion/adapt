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
        // return the original name
        if (!$this->configDTO->usingScenarioTestDBs()) {
            return $this->origDBName();
        }

        // or generate a new name
        $dbNameHash = $this->hasher->generateDatabaseNameHashPart(
            $this->configDTO->pickSeedersToInclude(),
            $this->configDTO->databaseModifier
        );
        return $this->dbAdapter()->name->generateScenarioDBName($dbNameHash);
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
