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
 * The ArrayExpression class represents an array SQL expression.
 *
 * Expressions of this type can be used in conditions as well:
 *
 * ```php
 * $query->andWhere(['@>', 'items', new ArrayExpression([1, 2, 3], 'integer')])
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
    public function __construct(private array $value = [], private string|null $type = null, private int $dimension = 1)
    {
    }

    /**
     * The type of the array elements. Defaults to `null` which means the type is not explicitly specified.
     *
     * Note that in case when type is not specified explicitly and DBMS can not guess it from the context, SQL error
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
    public function getValue(): array
    {
        return $this->value;
    }

    /**
     * @return int The number of indices needed to select an element
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    /**
     * Whether an offset exists.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset An offset to check for.
     *
     * @throws InvalidConfigException If offset is not an integer
     *
     * @return bool `true` on success or `false` on failure. The return value will be cast to boolean if non-boolean
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
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @throws InvalidConfigException If offset is not an integer
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet(mixed $offset): mixed
    {
        $key = $this->validateKey($offset);

        return $this->value[$key];
    }

    /**
     * Offset to set.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     *
     * @throws InvalidConfigException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $key = $this->validateKey($offset);

        $this->value[$key] = $value;
    }

    /**
     * Offset to unset.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->value[$offset]);
    }

    /**
     * Count elements of an object.
     *
     * @link http://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return count($this->value);
    }

    /**
     * Retrieve an external iterator.
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return ArrayIterator An instance of an object implementing <b>Iterator</b> or <b>Traversable</b>.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->value);
    }

    /**
     * Validates the key of the array expression is an integer.
     *
     * @throws InvalidConfigException
     */
    private function validateKey(mixed $key): int
    {
        if (!is_int($key)) {
            throw new InvalidConfigException('The ArrayExpression offset must be an integer.');
        }

        return $key;
    }
}
