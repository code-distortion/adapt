<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Exceptions\AdaptPropBagDTOException;

/**
 * Contain a list of properties and their values.
 */
abstract class PropBagDTO extends AbstractDTO
{
    /** @var mixed[] The properties and their values. */
    private $props = [];

    /**
     * Add a property.
     *
     * @param string $name  The name of the property.
     * @param mixed  $value The value to set.
     * @return static
     */
    public function addProp($name, $value): self
    {
        $this->props[$name] = $value;
        return $this;
    }

    /**
     * Retrieve a property.
     *
     * This will throw an exception if it doesn't exist and no default was passed.
     *
     * @param string|null $name    The name of the property.
     * @param mixed       $default The default value to fall back to.
     * @return mixed
     * @throws AdaptPropBagDTOException When the property hasn't been set.
     */
    public function prop($name, $default = null)
    {
        if (!$this->hasProp($name)) {
            $hasDefault = (func_num_args() >= 2);
            if (!$hasDefault) {
                throw AdaptPropBagDTOException::propertyDoesNotExist((string) $name);
            }
            return $default;
        }
        return $this->props[$name];
    }

    /**
     * Check if a property exists
     *
     * @param string|null $name The name of the property.
     * @return mixed
     */
    public function hasProp($name)
    {
        return isset($this->props[$name]);
    }

    /**
     * Get a property from $this - but fall back to adapt config values when not present.
     *
     * @param string      $configKey The key to this setting in the config.
     * @param string|null $propName  The setting to retrieve.
     * @param mixed       $default   The default value.
     * @return mixed
     */
    abstract public function adaptConfig($configKey, $propName = null, $default = null);

    /**
     * Get a property from $this - but fall back to config values when not present.
     *
     * @param string      $configKey The key to this setting in the config.
     * @param string|null $propName  The setting to retrieve.
     * @param mixed       $default   The default value.
     * @return mixed
     */
    abstract public function config($configKey, $propName = null, $default = null);
}
