<?php

namespace CodeDistortion\Adapt\DI\Injectable\Laravel;

use CodeDistortion\Adapt\Exceptions\AdaptConfigException;

/**
 * Injectable class to abstract some interaction with Laravel's config.
 */
class LaravelConfig
{
    /** @var string[] A record of each connection's original database name. */
    protected array $origDBNames = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->recordOrigDBNames();
    }

    /**
     * Remember the original database names.
     *
     * @return void
     */
    public function recordOrigDBNames(): void
    {
        foreach (config('database.connections') as $conName => $connection) {
            $this->origDBNames[$conName] = $connection['database'];
        }
    }

    /**
     * Retrieve a connection's original database name.
     *
     * @param string $connection The connection whose orig-database name to get.
     * @return string
     * @throws AdaptConfigException Thrown when the connection doesn't exist.
     */
    public function origDBName(string $connection): string
    {
        if (!isset($this->origDBNames[$connection])) {
            throw AdaptConfigException::invalidConnection($connection);
        }
        return $this->origDBNames[$connection];
    }
}
