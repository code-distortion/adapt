<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Support\Settings;

/**
 * Contain a list of properties and their values.
 */
class LaravelPropBagDTO extends PropBagDTO
{
    /**
     * Get a property from $this - but fall back to adapt config values when not present.
     *
     * @param string      $configKey The key to this setting in the config.
     * @param string|null $propName  The setting to retrieve.
     * @param mixed       $default   The default value.
     * @return mixed
     */
    public function adaptConfig($configKey, $propName = null, $default = null)
    {
        return $this->prop(
            $propName,
            config(Settings::LARAVEL_CONFIG_NAME . '.' . $configKey) ?? $default
        );
    }

    /**
     * Get a property from $this - but fall back to config values when not present.
     *
     * @param string      $configKey The key to this setting in the config.
     * @param string|null $propName  The setting to retrieve.
     * @param mixed       $default   The default value.
     * @return mixed
     */
    public function config($configKey, $propName = null, $default = null)
    {
        return $this->prop(
            $propName,
            config($configKey) ?? $default
        );
    }
}
