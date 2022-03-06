<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO\Support;

use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Support\HasConfigDTOTrait;

/**
 *
 */
class HasConfigDTOClass
{
    use HasConfigDTOTrait;

    /**
     * Constructor.
     *
     * @param ConfigDTO $configDTO The ConfigDTO to use.
     */
    public function __construct(ConfigDTO $configDTO)
    {
        $this->configDTO = $configDTO;
    }
}
