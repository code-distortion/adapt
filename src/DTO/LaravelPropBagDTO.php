<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Support\LaravelSettingsTrait;

/**
 * Contain a list of properties and their values.
 */
class LaravelPropBagDTO extends PropBagDTO
{
    use LaravelSettingsTrait;

    /**
     * Get a property from $this - but fall back to config values when not present.
     *
     * @param string      $configKey The key to this setting in the config.
     * @param string|null $propName  The setting to retrieve.
     * @return mixed
     */
    public function config(string $configKey, string $propName = null)
    {
        return $this->prop(
            $propName,
            config($this->configName.'.'.$configKey)
        );
    }
}
