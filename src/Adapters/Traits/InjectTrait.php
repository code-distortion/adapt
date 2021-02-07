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
    protected $di;

    /** @var ConfigDTO A DTO containing the settings to use. */
    protected $config;


    /**
     * Constructor.
     *
     * @param DIContainer $di     The dependency-injection container to use.
     * @param ConfigDTO   $config A DTO containing the settings to use.
     */
    public function __construct(DIContainer $di, ConfigDTO $config)
    {
        $this->di = $di;
        $this->config = $config;
    }
}
