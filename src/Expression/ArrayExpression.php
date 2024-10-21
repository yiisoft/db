<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Syntax\ArrayParserInterface;

use function array_map;
use function array_walk_recursive;
use function count;
use function is_array;
use function is_int;
use function is_string;
use function iterator_to_array;

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
 * @template-implements IteratorAggregate<mixed, mixed>
 */
final class ArrayExpression implements ExpressionInterface, ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @param ExpressionInterface|iterable|string $value The array's content. In can be represented as
     * - an array of values;
     * - an instance of {@see Traversable} that represents an array of values;
     * - an instance of {@see ExpressionInterface} that represents an SQL expression (e.g. a sub-query)
     * - a string retrieved value from the database that can be parsed into an array.
     * @param string|null $type The type of the array elements. Defaults to `null` which means the type isn't
     * explicitly specified. Note that in the case where a type isn't specified explicitly and DBMS can't guess it from
     * the context, SQL error will be raised.
     * @param int $dimension The number of indices needed to select an element.
     * @param ColumnSchemaInterface|null $column The column schema information. This is used to typecast values.
     *
     * @psalm-param positive-int $dimension
     */
    public function __construct(
        private iterable|string|ExpressionInterface $value = [],
        private readonly string|null $type = null,
        private readonly int $dimension = 1,
        private readonly ColumnSchemaInterface|null $column = null,
        private readonly ArrayParserInterface|null $parser = null
    ) {
    }

    /**
     * The column schema information. This is used to typecast values.
     */
    public function getColumn(): ColumnSchemaInterface|null
    {
        return $this->column;
    }

    /**
     * The number of indices needed to select an element.
     *
     * @psalm-return positive-int
     */
    public function getDimension(): int
    {
        return $this->dimension;
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
     * The array's content. In can be represented as
     *  - an array of values;
     *  - an instance of {@see Traversable} that represents an array of values;
     *  - an instance of {@see ExpressionInterface} that represents an SQL expression (e.g. a sub-query)
     * - a string retrieved value from the database that can be parsed into an array.
     */
    public function getValue(): iterable|string|ExpressionInterface
    {
        return $this->value;
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
        $this->validateKey($offset);
        $this->prepareValue();

        return isset($this->value[$offset]);
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
        $this->validateKey($offset);
        $this->prepareValue();

        return $this->value[$offset];
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
        $this->validateKey($offset);
        $this->prepareValue();

        $this->value[$offset] = $value;
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
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        $this->validateKey($offset);
        $this->prepareValue();

        unset($this->value[$offset]);
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
        $this->prepareValue();

        return count((array) $this->value);
    }

    /**
     * Retrieve an external iterator.
     *
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @throws InvalidConfigException If value isn't an array.
     *
     * @return Traversable An instance of an object implementing `Iterator` or `Traversable`.
     */
    public function getIterator(): Traversable
    {
        if ($this->value instanceof Traversable) {
            return $this->value;
        }

        $this->prepareValue();

        /** @psalm-suppress PossiblyInvalidArgument */
        return new ArrayIterator($this->value);
    }

    /**
     * Converts the value to an array.
     *
     * @throws InvalidConfigException If the value cannot be converted to an array.
     */
    public function toArray(): array
    {
        if (is_string($this->value)) {
            $value = $this->parse($this->value);
            return $this->phpTypecast($value);
        }

        if (is_array($this->value)) {
            return $this->value;
        }

        if ($this->value instanceof Traversable) {
            return iterator_to_array($this->value, false);
        }

        throw new InvalidConfigException('The ArrayExpression value cannot be converted to array.');
    }

    private function parse(string $value): array
    {
        if ($this->parser === null) {
            throw new InvalidConfigException('The ArrayExpression parser must be set to parse the string value.');
        }

        $parsed = $this->parser->parse($value);

        if ($parsed === null) {
            throw new InvalidConfigException('The ArrayExpression value cannot be parsed into array.');
        }

        return $parsed;
    }

    private function phpTypecast(array $value): array
    {
        if ($this->column === null || $this->column->getType() === ColumnType::STRING) {
            return $value;
        }

        if ($this->dimension === 1 && $this->column->getType() !== ColumnType::JSON) {
            return array_map($this->column->phpTypecast(...), $value);
        }

        array_walk_recursive($value, function (string|null &$val): void {
            /** @psalm-suppress PossiblyNullReference */
            $val = $this->column->phpTypecast($val);
        });

        return $value;
    }

    /**
     * Prepares the value to be used as an array or throws an exception if it's impossible.
     *
     * @throws InvalidConfigException If value isn't an array.
     *
     * @psalm-assert array|ArrayAccess $this->value
     */
    private function prepareValue(): void
    {
        if (!is_array($this->value)) {
            $this->value = $this->toArray();
        }
    }

    /**
     * Validates the key of the array expression is an integer.
     *
     * @throws InvalidConfigException If offset isn't an integer.
     *
     * @psalm-assert int $key
     */
    private function validateKey(mixed $key): void
    {
        if (!is_int($key)) {
            throw new InvalidConfigException('The ArrayExpression offset must be an integer.');
        }
    }
}
