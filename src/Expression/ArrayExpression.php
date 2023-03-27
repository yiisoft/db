<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\QueryInterface;

use function count;

/**
 * Represents an array SQL expression.
 *
 * Expressions of this type can be used in conditions as well:
 *
 * ```php
 * $query->andWhere(['@>', 'items', new ArrayExpression([1, 2, 3], 'integer')]);
 * ```
 *
 * Which, depending on DBMS, will result in a well-prepared condition. For example, in PostgresSQL it will be compiled
 * to `WHERE "items" @> ARRAY[1, 2, 3]::integer[]`.
 *
 * @template-implements ArrayAccess<int, mixed>
 * @template-implements IteratorAggregate<int>
 */
class ArrayExpression implements ExpressionInterface, ArrayAccess, Countable, IteratorAggregate
{
    public function __construct(private mixed $value = [], private string|null $type = null, private int $dimension = 1)
    {
    }

    /**
     * The type of the array elements.
     *
     * Defaults to `null` which means the type isn't explicitly specified.
     *
     * Note that in the case where a type isn't specified explicitly and DBMS can't guess it from the context, SQL error
     * will be raised.
     */
    public function getType(): string|null
    {
        return $this->type;
    }

    /**
     * The array's content. In can be represented as an array of values or a {@see QueryInterface} that returns these
     * values.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return int The number of indices needed to select an element.
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    /**
     * Whether an offset exists.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset An offset to check for.
     *
     * @throws InvalidConfigException If offset isn't an integer.
     *
     * @return bool Its `true` on success or `false` on failure. The return value will be cast to boolean if non-boolean
     * was returned.
     */
    public function offsetExists(mixed $offset): bool
    {
        $key = $this->validateKey($offset);
        return isset($this->value[$key]);
    }

    /**
     * Offset to retrieve.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @throws InvalidConfigException If offset isn't an integer.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet(mixed $offset): mixed
    {
        $key = $this->validateKey($offset);
        $this->value = $this->validateValue($this->value);
        return $this->value[$key];
    }

    /**
     * Offset to set.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     *
     * @throws InvalidConfigException If offset isn't an integer.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $key = $this->validateKey($offset);
        $this->value = $this->validateValue($this->value);
        $this->value[$key] = $value;
    }

    /**
     * Offset to unset.
     *
     * @throws InvalidConfigException If offset isn't an integer.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset(mixed $offset): void
    {
        $key = $this->validateKey($offset);
        $this->value = $this->validateValue($this->value);
        unset($this->value[$key]);
    }

    /**
     * Count elements of an object.
     *
     * @link https://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return count((array) $this->value);
    }

    /**
     * Retrieve an external iterator.
     *
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @throws InvalidConfigException If value isn't an array.
     *
     * @return ArrayIterator An instance of an object implementing `Iterator` or `Traversable`.
     */
    public function getIterator(): Traversable
    {
        $value = $this->validateValue($this->value);
        return new ArrayIterator($value);
    }

    /**
     * Validates the key of the array expression is an integer.
     *
     * @throws InvalidConfigException If offset isn't an integer.
     */
    private function validateKey(mixed $key): int
    {
        if (!is_int($key)) {
            throw new InvalidConfigException('The ArrayExpression offset must be an integer.');
        }

        return $key;
    }

    /**
     * Validates the value of the array expression is an array.
     *
     * @throws InvalidConfigException If value isn't an array.
     */
    private function validateValue(mixed $value): array
    {
        if (!is_array($value)) {
            throw new InvalidConfigException('The ArrayExpression value must be an array.');
        }

        return $value;
    }
}
