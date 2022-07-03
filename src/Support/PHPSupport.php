<?php

namespace CodeDistortion\Adapt\Support;

use Laravel\SerializableClosure\Support\ReflectionClosure;
use ReflectionClass;
use ReflectionException;
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
    public static function readPrivateProperty(object $object, string $propertyName)
    {
        $reflection = new ReflectionObject($object);

        if (!$reflection->hasProperty($propertyName)) {
            return null;
        }

        $prop = $reflection->getProperty($propertyName);
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
    public static function readPrivateStaticProperty(string $class, string $propertyName)
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
    public static function updatePrivateProperty(object $object, string $propertyName, mixed $newValue): void
    {
        $reflection = new ReflectionObject($object);

        if (!$reflection->hasProperty($propertyName)) {
            return;
        }

        $prop = $reflection->getProperty($propertyName);
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
    public static function getClassMethodFirstParameterType(string $class, string $method): ?string
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
    public static function getCallableFirstParameterType(callable $closure): ?string
    {
        $reflectionClosure = new ReflectionClosure($closure);

        if ($reflectionClosure->getNumberOfParameters() < 1) {
            return null;
        }

        $reflectionParameter = $reflectionClosure->getParameters()[0];
        return $reflectionParameter->getType();
    }
}
