<?php

namespace CodeDistortion\Adapt\DTO\Traits;

trait DTOBuildTrait
{
    /**
     * Build a new instance of this DTO, populated with certain values.
     *
     * @param array $values
     * @return static
     */
    protected static function buildFromArray($values): self
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
