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
    private function pickDatabaseNameAndUse(): void
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
        $dbNameHashPart = $this->configDTO->usingScenarioTestDBs() ? $this->generateDatabaseNameHashPart() : null;

        return $this->dbAdapter()->name->generateDBName($this->configDTO->usingScenarioTestDBs(), $dbNameHashPart);
    }

    /**
     * Generate a hash to use in the database name.
     *
     * Based on the source-files hash, extended-scenario hash.
     *
     * @return string
     */
    private function generateDatabaseNameHashPart(): string
    {
        return $this->hasher->generateDatabaseNameHashPart(
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
    private function useDatabase(string $name): void
    {
        $this->dbAdapter()->connection->useDatabase($name);
    }

    /**
     * Use the desired database - with no logging.
     *
     * @param string $name The name of the database to use.
     * @return void
     */
    private function silentlyUseDatabase(string $name): void
    {
        $this->dbAdapter()->connection->useDatabase($name, false);
    }

    /**
     * Get the database currently being used.
     *
     * @return string|null
     */
    private function getCurrentDatabase(): ?string
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
