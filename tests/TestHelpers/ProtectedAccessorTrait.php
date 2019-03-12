<?php declare(strict_types=1);

namespace OAT\DependencyResolver\TestHelpers;

use ReflectionProperty;

trait ProtectedAccessorTrait
{
    public function getPrivateProperty($object, $property)
    {
        $reflector = new ReflectionProperty(get_class($object), $property);
        $reflector->setAccessible(true);
        $value = $reflector->getValue($object);
        $reflector->setAccessible(false);
        return $value;
    }
}
