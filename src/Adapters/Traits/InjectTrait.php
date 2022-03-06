<?php

namespace CodeDistortion\Adapt\Adapters\Traits;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * Database-adapter trait to handle the injection of a DIContainer and a ConfigDTO.
 */
trait InjectTrait
{
    /** @var DIContainer The dependency-injection container to use. */
    protected DIContainer $di;

    /** @var ConfigDTO A DTO containing the settings to use. */
    protected ConfigDTO $configDTO;


    /**
     * Constructor.
     *
     * @param DIContainer $di        The dependency-injection container to use.
     * @param ConfigDTO   $configDTO A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO)
    {
        $this->di = $di;
        $this->configDTO = $configDTO;
    }
}
