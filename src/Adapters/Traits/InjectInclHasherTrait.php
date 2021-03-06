<?php

namespace CodeDistortion\Adapt\Adapters\Traits;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Support\Hasher;

/**
 * Database-adapter trait to handle the injection of a DIContainer, ConfigDTO and Hasher.
 */
trait InjectInclHasherTrait
{
    /** @var DIContainer The dependency-injection container to use. */
    protected $di;

    /** @var ConfigDTO A DTO containing the settings to use. */
    protected $config;

    /** @var Hasher A Hasher that's used to generate and check hashes. */
    protected $hasher;


    /**
     * Constructor.
     *
     * @param DIContainer $di     The dependency-injection container to use.
     * @param ConfigDTO   $config A DTO containing the settings to use.
     * @param Hasher      $hasher The object used to generate and check hashes.
     */
    public function __construct(DIContainer $di, ConfigDTO $config, Hasher $hasher)
    {
        $this->di = $di;
        $this->config = $config;
        $this->hasher = $hasher;
    }
}
