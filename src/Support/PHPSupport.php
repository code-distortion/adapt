<?php

namespace CodeDistortion\Adapt\Support;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionObject;

/**
 * Provides extra miscellaneous PHP related support functionality.
 */
class PHPSupport
{
    /**
     * Read a class's private property.
     *
     * @param object $object       The object to look in to.
     * @param string $propertyName The property to read.
     * @return mixed
     */
    public static function readPrivateProperty($object, $propertyName)
    {
        $reflectionObject = new ReflectionObject($object);

        if (!$reflectionObject->hasProperty($propertyName)) {
            return null;
        }

        $prop = $reflectionObject->getProperty($propertyName);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    /**
     * Read a class's STATIC private property.
     *
     * @param string $class        The class to look in to.
     * @param string $propertyName The property to read.
     * @return mixed
     */
    public static function readPrivateStaticProperty($class, $propertyName)
    {
        if (!class_exists($class)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($class);

        if (!$reflectionClass->hasProperty($propertyName)) {
            return null;
        }

        $prop = $reflectionClass->getProperty($propertyName);
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
        $reflectionObject = new ReflectionObject($object);

        if (!$reflectionObject->hasProperty($propertyName)) {
            return;
        }

        $prop = $reflectionObject->getProperty($propertyName);
        $prop->setAccessible(true);
        $prop->setValue($object, $newValue);
    }

    /**
     * Get the class type of the first parameter of the given class method.
     *
     * @param string $class  The class to look in to.
     * @param string $method The method to look in to.
     * @return string|null
     * @throws ReflectionException
     */
    public static function getClassMethodFirstParameterType($class, $method)
    {
        $reflectionClass = new ReflectionClass($class);

        $reflectionMethod = $reflectionClass->getMethod($method);

        if ($reflectionMethod->getNumberOfParameters() < 1) {
            return null;
        }

        $reflectionParameter = $reflectionMethod->getParameters()[0];
        return $reflectionParameter->getType();
    }

    /**
     * Get the class type of the first parameter of the given closure.
     *
     * @param callable $closure The closure to look in to.
     * @return string|null
     */
    public static function getCallableFirstParameterType($closure)
    {
        $reflectionFunction = new ReflectionFunction($closure);

        if ($reflectionFunction->getNumberOfParameters() < 1) {
            return null;
        }

        $reflectionParameter = $reflectionFunction->getParameters()[0];
        return $reflectionParameter->getType()->getName();
    }
}
