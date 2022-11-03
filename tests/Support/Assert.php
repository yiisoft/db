<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;

final class Assert extends TestCase
{
    /**
     * Gets an inaccessible object property.
     *
     * @param bool $revoke whether to make property inaccessible after getting.
     */
    public static function getInaccessibleProperty(object $object, string $propertyName, bool $revoke = true): mixed
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        $property = $class->getProperty($propertyName);

        $property->setAccessible(true);

        $result = $property->getValue($object);

        if ($revoke) {
            $property->setAccessible(false);
        }

        return $result;
    }

    /**
     * Invokes an inaccessible method.
     *
     * @param object $object The object to invoke the method on.
     * @param string $method The name of the method to invoke.
     * @param array $args The arguments to pass to the method.
     *
     * @throws ReflectionException
     */
    public static function invokeMethod(object $object, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionObject($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
