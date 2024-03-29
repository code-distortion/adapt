<?php

namespace CodeDistortion\Adapt\Adapters\Interfaces;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * Database-adapter methods related to managing a database connection.
 */
interface ConnectionInterface
{
    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO);



    /**
     * Set this builder's database connection as the default one.
     *
     * @return void
     */
    public function makeThisConnectionDefault();

    /**
     * Tell the adapter to use the given database name.
     *
     * @param string  $database     The name of the database to use.
     * @param boolean $applyLogging Enable or disable logging.
     * @return void
     */
    public function useDatabase($database, $applyLogging = true);

    /**
     * Get the database currently being used.
     *
     * @return string|null
     */
    public function getDatabase();
}
