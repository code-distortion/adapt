<?php

namespace CodeDistortion\Adapt\Support;

use ReflectionClass;
use ReflectionObject;

/**
 * Provides extra miscellaneous PHP related support functionality.
 */
class PHPSupport
{
    /**
     * Read a class's STATIC private property.
     *
     * @param string $class        The class to look in to.
     * @param string $propertyName The property to read.
     * @return mixed
     */
    public static function readStaticPrivateProperty($class, $propertyName)
    {
        if (!class_exists($class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->hasProperty($propertyName)) {
            return null;
        }

        $prop = $reflection->getProperty($propertyName);
        $prop->setAccessible(true);
        return $prop->getValue();
    }

    /**
     * Update an object's private property.
     *
     * @param object $object       The object to alter.
     * @param string $propertyName The property to update.
     * @param mixed  $newValue     The value to set.
     * @return void
     */
    public static function updatePrivateProperty($object, $propertyName, $newValue)
    {
        $reflection = new ReflectionObject($object);

        if (!$reflection->hasProperty($propertyName)) {
            return;
        }

        $prop = $reflection->getProperty($propertyName);
        $prop->setAccessible(true);
        $prop->setValue($object, $newValue);
    }
}
