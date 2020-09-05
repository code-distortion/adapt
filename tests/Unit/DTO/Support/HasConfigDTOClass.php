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
     * @param ConfigDTO $config The ConfigDTO to use.
     */
    public function __construct(ConfigDTO $config)
    {
        $this->config = $config;
    }
}
