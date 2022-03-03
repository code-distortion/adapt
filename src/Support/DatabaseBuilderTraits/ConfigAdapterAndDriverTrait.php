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
    private ?DBAdapter $dbAdapter = null;



    /**
     * Create a database adapter to do the database specific work.
     *
     * @return DBAdapter
     * @throws AdaptConfigException Thrown when the type of database isn't recognised.
     */
    private function dbAdapter(): DBAdapter
    {
        if (!is_null($this->dbAdapter)) {
            return $this->dbAdapter;
        }

        // build a new one...
        $driver = $this->pickDriver();
        $framework = $this->framework;
        if (
            (!isset($this->availableDBAdapters[$framework]))
            || (!isset($this->availableDBAdapters[$framework][$driver]))
        ) {
            throw AdaptConfigException::unsupportedDriver($this->config->connection, $driver);
        }

        $adapterClass = $this->availableDBAdapters[$framework][$driver];
        /** @var DBAdapter $dbAdapter */
        $dbAdapter = new $adapterClass($this->di, $this->config, $this->hasher);
        $this->dbAdapter = $dbAdapter;

        return $this->dbAdapter;
    }

    /**
     * Retrieve the database-driver being used.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->pickDriver();
    }

    /**
     * Pick a database driver for the given connection.
     *
     * @return string
     */
    private function pickDriver(): string
    {
        $pickDriver = $this->pickDriverClosure;
        return $this->config->driver = $pickDriver($this->config->connection);
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
        if (!$this->config->database) {
            $this->pickDatabaseNameAndUse();
        }
        return (string) $this->config->database;
    }
}
