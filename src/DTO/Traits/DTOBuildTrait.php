<?php

namespace CodeDistortion\Adapt\DTO\Traits;

use CodeDistortion\Adapt\DTO\VersionsDTO;

/**
 * Trait that provides DTO building functionality.
 */
trait DTOBuildTrait
{
    /**
     * Build a new instance of this DTO, populated with certain values.
     *
     * @param array<string, mixed> $values The source values to read from.
     * @return static
     */
    protected static function buildFromArray($values)
    {
        $remoteShareDTO = new static();
        foreach ($values as $name => $value) {
            if (property_exists($remoteShareDTO, $name)) {

                if ((is_array($value)) && (in_array($name, ['versionsDTO', 'remoteVersionsDTO']))) {
                    $remoteShareDTO->{$name} = VersionsDTO::buildFromArray($value);
                } else {
                    $remoteShareDTO->{$name} = $value;
                }
            }
        }
        return $remoteShareDTO;
    }
}
