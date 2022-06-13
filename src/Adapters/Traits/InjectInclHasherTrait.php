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
    protected $configDTO;

    /** @var Hasher A Hasher that's used to generate and check checksums. */
    protected $hasher;


    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     * @param Hasher      $hasher    The object used to generate and check checksums.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO, Hasher $hasher)
    {
        $this->di = $di;
        $this->configDTO = $configDTO;
        $this->hasher = $hasher;
    }
}
