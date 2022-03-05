<?php

namespace CodeDistortion\Adapt\DTO\Traits;

trait DTOBuildTrait
{
    /**
     * Build a new instance of this DTO, populated with certain values.
     *
     * @param array<string, mixed> $values The source values to read from.
     * @return self
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
