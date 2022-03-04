<?php

namespace CodeDistortion\Adapt\DTO\Traits;

trait DTOBuildTrait
{
    /**
     * Build a new instance of this DTO, populated with certain values.
     *
     * @param array $values The source values to read from.
     * @return static
     */
    protected static function buildFromArray(array $values): self
    {
        $remoteShareDTO = new self();
        foreach ($values as $name => $value) {
            if (property_exists($remoteShareDTO, $name)) {
                $remoteShareDTO->{$name} = $value;
            }
        }
        return $remoteShareDTO;
    }
}
