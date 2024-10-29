<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Traversable;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;

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
 */
final class ArrayExpression implements ExpressionInterface
{
    /**
     * @param iterable|LazyArrayInterface|QueryInterface $value The array's content. In can be represented as
     * - an array of values;
     * - an instance of {@see Traversable} or {@see LazyArrayInterface} that represents an array of values;
     * - an instance of {@see QueryInterface} that represents an SQL sub-query.
     * @param string|null $type The type of the array elements. Defaults to `null` which means the type isn't
     * explicitly specified. Note that in the case where a type isn't specified explicitly and DBMS can't guess it from
     * the context, SQL error will be raised.
     * @param int $dimension The number of indices needed to select an element.
     * @param ColumnSchemaInterface|null $column The column schema information used to typecast values.
     *
     * @psalm-param positive-int $dimension
     */
    public function __construct(
        private readonly iterable|LazyArrayInterface|QueryInterface $value,
        private readonly string|null $type = null,
        private readonly int $dimension = 1,
        private readonly ColumnSchemaInterface|null $column = null
    ) {
    }

    /**
     * The column schema information used to typecast values.
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
     * - an array of values;
     * - an instance of {@see Traversable} or {@see LazyArrayInterface} that represents an array of values;
     * - an instance of {@see QueryInterface} that represents an SQL sub-query.
     */
    public function getValue(): iterable|LazyArrayInterface|QueryInterface
    {
        return $this->value;
    }
}
