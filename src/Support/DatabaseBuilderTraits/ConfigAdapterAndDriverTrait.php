<?php

namespace CodeDistortion\Adapt\Support\DatabaseBuilderTraits;

use CodeDistortion\Adapt\Adapters\DBAdapter;
use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;

/**
 * @mixin DatabaseBuilder
 */
trait ConfigAdapterAndDriverTrait
{
    /** @var DBAdapter|null The object that will do the database specific work. */
    private $dbAdapter;



    /**
     * Reset the dbAdapter, force it to be resolved again.
     *
     * @return void
     */
    private function resetDbAdapter()
    {
        $this->dbAdapter = null;
    }

    /**
     * Create a database adapter to do the database specific work.
     *
     * @return DBAdapter
     * @throws AdaptConfigException When the type of database isn't recognised.
     */
    private function dbAdapter(): DBAdapter
    {
        if (!is_null($this->dbAdapter)) {
            return $this->dbAdapter;
        }

        // build a new one...
        $driver = $this->pickDriver();
        $framework = $this->framework;
        if (!($this->availableDBAdapters[$framework][$driver] ?? null)) {
            throw AdaptConfigException::unsupportedDriver($this->configDTO->connection, $driver);
        }

        $adapterClass = $this->availableDBAdapters[$framework][$driver];
        /** @var DBAdapter $dbAdapter */
        $dbAdapter = new $adapterClass($this->di, $this->configDTO);
        $this->dbAdapter = $dbAdapter;

        $this->di->db->useConnection($this->configDTO->connection);

        return $this->dbAdapter;
    }

    /**
     * Pick a database driver for the given connection.
     *
     * @return string
     */
    private function pickDriver(): string
    {
        $pickDriver = $this->pickDriverClosure;
        return $this->configDTO->driver = $pickDriver($this->configDTO->connection);
    }



    /**
     * Set this builder's database connection to be the "default" one.
     *
     * @return static
     */
    public function makeDefault(): self
    {
        $this->dbAdapter()->connection->makeThisConnectionDefault();
        return $this;
    }

    /**
     * Retrieve the name of the database being used.
     *
     * @return string
     */
    public function getResolvedDatabase(): string
    {
        if (!$this->configDTO->database) {
            $this->pickDatabaseNameAndUse();
        }
        return (string) $this->configDTO->database;
    }
}
