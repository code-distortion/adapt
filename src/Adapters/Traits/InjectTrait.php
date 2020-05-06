<?php

namespace CodeDistortion\Adapt\Adapters\Traits;

use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use Illuminate\Database\Connection;

/**
 * Database-adapter trait to handle the injection of a DIContainer and a ConfigDTO.
 */
trait InjectTrait
{
    /**
     * The dependency-injection container to use.
     *
     * @var DIContainer
     */
    protected DIContainer $di;

    /**
     * A DTO containing the settings to use.
     *
     * @var ConfigDTO
     */
    protected ConfigDTO $config;


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
