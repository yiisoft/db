<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Data;

use ArrayIterator;

use function count;

/**
 * Provides a common implementation for {@see AbstractLazyArray} and {@see AbstractLazyArrayStructured}.
 *
 * @method void prepareValue() Prepares the value to be used as an array or throws an exception if it's impossible.
 */
trait LazyArrayTrait
{
    /**
     * The raw value that can be represented as:
     * - a string retrieved value from the database that can be parsed into an array;
     * - an array of values if the value is already parsed.
     */
    public function getRawValue(): array|string
    {
        return $this->value;
    }

    /**
     * Returns parsed and typecasted value.
     */
    public function getValue(): array
    {
        $this->prepareValue();

        return $this->value;
    }

    public function jsonSerialize(): array
    {
        return $this->getValue();
    }

    /**
     * @param int|string $offset The offset to check.
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->prepareValue();

        return isset($this->value[$offset]);
    }

    /**
     * @param int|string $offset The offset to retrieve.
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->prepareValue();

        return $this->value[$offset];
    }

    /**
     * @param int|string $offset The offset to assign the value to.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->prepareValue();

        $this->value[$offset] = $value;
    }

    /**
     * @param int|string $offset The offset to unset.
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->prepareValue();

        unset($this->value[$offset]);
    }

    public function count(): int
    {
        $this->prepareValue();

        return count($this->value);
    }

    public function getIterator(): ArrayIterator
    {
        $this->prepareValue();

        return new ArrayIterator($this->value);
    }
}
