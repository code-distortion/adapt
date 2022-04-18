<?php

namespace CodeDistortion\Adapt\Adapters\Traits;

use CodeDistortion\Adapt\Adapters\Interfaces\VerifierInterface;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DTO\ConfigDTO;

/**
 * Database-adapter trait to handle the injection of a DIContainer, ConfigDTO and Verifier.
 */
trait InjectInclVerifierTrait
{
    /** @var DIContainer The dependency-injection container to use. */
    protected $di;

    /** @var ConfigDTO A DTO containing the settings to use. */
    protected $configDTO;

    /** @var VerifierInterface A Verifier that's used to get primary-keys and verify database structure. */
    protected $verifier;


    /**
     * Constructor.
     *
     * @param DIContainer       $di        The dependency-injection container to use.
     * @param ConfigDTO         $configDTO A DTO containing the settings to use.
     * @param VerifierInterface $verifier  The validator, used to get primary-keys and verify database structure.
     */
    public function __construct(DIContainer $di, ConfigDTO $configDTO, VerifierInterface $verifier)
    {
        $this->di = $di;
        $this->configDTO = $configDTO;
        $this->verifier = $verifier;
    }
}
