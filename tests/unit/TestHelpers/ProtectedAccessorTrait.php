<?php declare(strict_types=1);

namespace OAT\DependencyResolver\TestHelpers;

use ReflectionProperty;

trait ProtectedAccessorTrait
{
    /**
     * Gets a private property value of the given object.
     *
     * @param mixed  $object
     * @param string $property
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function getPrivateProperty($object, string $property)
    {
        $reflector = new ReflectionProperty(get_class($object), $property);
        $reflector->setAccessible(true);
        $value = $reflector->getValue($object);
        $reflector->setAccessible(false);
        return $value;
    }

    /**
     * Sets a private property value of the given object.
     *
     * @param mixed  $object
     * @param string $property
     * @param mixed  $value
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function setPrivateProperty($object, string $property, $value)
    {
        $reflector = new ReflectionProperty(get_class($object), $property);
        $reflector->setAccessible(true);
        $reflector->setValue($object, $value);
        $reflector->setAccessible(false);
        return $value;
    }
}
