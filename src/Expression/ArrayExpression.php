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
 * Class ArrayExpression represents an array SQL expression.
 *
 * Expressions of this type can be used in conditions as well:
 *
 * ```php
 * $query->andWhere(['@>', 'items', new ArrayExpression([1, 2, 3], 'integer')])
 * ```
 *
 * Which, depending on DBMS, will result in a well-prepared condition. For example, in PostgresSQL it will be compiled
 * to `WHERE "items" @> ARRAY[1, 2, 3]::integer[]`.
 */
class ArrayExpression implements ExpressionInterface, ArrayAccess, Countable, IteratorAggregate
{
    public function __construct(private mixed $value = [], private ?string $type = null, private int $dimension = 1)
    {
    }

    /**
     * The type of the array elements. Defaults to `null` which means the type is not explicitly specified.
     *
     * Note that in case when type is not specified explicitly and DBMS can not guess it from the context, SQL error
     * will be raised.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * The array's content. In can be represented as an array of values or a {@see QueryInterface} that returns these
     * values.
     *
     * @return mixed
     */
    public function getValue(): mixed
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
     * @return bool true On success or false on failure.
     *
     * The return value will be cast to boolean if non-boolean was returned.
     *
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedAssignment
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->value[$offset]);
    }

    /**
     * Offset to retrieve.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     *
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArrayOffset
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->value[$offset];
    }

    /**
     * Offset to set.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     *
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedArrayAssignment
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->value[$offset] = $value;
    }

    /**
     * Offset to unset.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset
     *
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArrayOffset
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
     *
     * The return value is cast to an integer.
     */
    public function count(): int
    {
        return count((array) $this->value);
    }

    /**
     * Retrieve an external iterator.
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @throws InvalidConfigException When ArrayExpression contains QueryInterface object.
     *
     * @return ArrayIterator An instance of an object implementing <b>Iterator</b> or <b>Traversable</b>.
     */
    public function getIterator(): Traversable
    {
        if (!is_array($this->value)) {
            throw new InvalidConfigException('The ArrayExpression value must be an array.');
        }

        return new ArrayIterator($this->value);
    }
}
