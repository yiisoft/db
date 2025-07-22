<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;

use function array_key_exists;
use function is_array;
use function is_object;
use function ltrim;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class Assert extends TestCase
{
    /**
     * Asserts that value is one of expected values.
     */
    public static function isOneOf(mixed $actual, array $expected, string $message = ''): void
    {
        self::assertThat($actual, new IsOneOfAssert($expected), $message);
    }

    /**
     * Asserting two strings equality ignoring line endings.
     */
    public static function equalsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        self::assertEquals($expected, $actual, $message);
    }

    /**
     * Gets an inaccessible object property.
     */
    public static function getPropertyValue(object $object, string $propertyName): mixed
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        return $class->getProperty($propertyName)->getValue($object);
    }

    /**
     * Sets an inaccessible object property.
     *
     * @param object $object The object to set the property on.
     * @param string $propertyName The name of the property to set.
     * @param mixed $value The value to set.
     */
    public static function setPropertyValue(object &$object, string $propertyName, mixed $value): void
    {
        $class = new ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        $property = $class->getProperty($propertyName);

        if ($property->isReadOnly()) {
            $object = self::cloneObjectWith($object, [$propertyName => $value]);
        } else {
            $property->setValue($object, $value);
        }
    }

    public static function cloneObjectWith(object $object, array $values): object
    {
        $class = new ReflectionClass($object);
        $new = $class->newInstanceWithoutConstructor();

        $setProperty = fn (string $name, mixed $value) => $this->$name = $value;

        foreach ($class->getProperties() as $property) {
            if (array_key_exists($property->name, $values)) {
                $value = $values[$property->name];
            } elseif ($property->isInitialized($object)) {
                $value = $property->getValue($object);
            } else {
                continue;
            }

            $propertyClass = $property->getDeclaringClass()->name;

            if ($propertyClass === $object::class) {
                $property->setValue($new, $value);
            } else {
                $setProperty->bindTo($new, $propertyClass)($property->name, $value);
            }
        }

        return $new;
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

        return $method->invokeArgs($object, $args);
    }

    public static function arraysEquals(array $expected, mixed $actual, string $message = ''): void
    {
        self::assertIsArray($actual, $message);
        self::assertCount(count($expected), $actual, $message);

        foreach ($expected as $key => $value) {
            self::assertArrayHasKey($key, $actual, $message);

            $assertionMessage = ltrim($message . " Item by key '$key'.");

            if (is_object($value)) {
                self::objectsEquals($value, $actual[$key], $assertionMessage);
            } elseif (is_array($value)) {
                self::arraysEquals($value, $actual[$key], $assertionMessage);
            } else {
                self::assertSame($value, $actual[$key], $assertionMessage);
            }
        }
    }

    public static function objectsEquals(object $expected, mixed $actual, string $message = ''): void
    {
        self::assertIsObject($actual, $message);

        self::assertSame(
            $expected::class,
            $actual::class,
            'Expected ' . $expected::class . ' class name but ' . $actual::class . " provided. $message",
        );

        $properties = self::getProperties($expected);

        foreach ($properties as $property) {
            $assertionMessage = ltrim("$message " . $expected::class . "::{$property->getName()} property.");

            if (!$property->isInitialized($expected)) {
                self::assertFalse($property->isInitialized($actual), "$assertionMessage Property should not be initialized.");

                continue;
            }

            self::assertTrue($property->isInitialized($actual), "$assertionMessage Property is not initialized.");

            $expectedValue = $property->getValue($expected);
            $actualValue = $property->getValue($actual);

            if (is_object($expectedValue)) {
                self::objectsEquals($expectedValue, $actualValue, $assertionMessage);
            } elseif (is_array($expectedValue)) {
                self::arraysEquals($expectedValue, $actualValue, $assertionMessage);
            } else {
                self::assertSame($expectedValue, $actualValue, $assertionMessage);
            }
        }
    }

    /**
     * Returns all properties of the object, including inherited ones.
     *
     * @return ReflectionProperty[]
     */
    private static function getProperties(object $object): array
    {
        $properties = [];
        $reflectionClass = new ReflectionClass($object);

        do {
            foreach ($reflectionClass->getProperties() as $property) {
                $properties[$property->getName()] ??= $property;
            }
        } while ($reflectionClass = $reflectionClass->getParentClass());

        return $properties;
    }
}
