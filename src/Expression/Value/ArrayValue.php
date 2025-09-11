<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use Traversable;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\ColumnDefinitionBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;

/**
 * Represents an array SQL expression.
 *
 * Expressions of this type can be used in conditions as well:
 *
 * ```php
 * $query->andWhere(['@>', 'items', new ArrayValue([1, 2, 3], 'integer[]')]);
 * ```
 *
 * Which, depending on DBMS, will result in a well-prepared condition. For example, in PostgresSQL it will be compiled
 * to `WHERE "items" @> ARRAY[1, 2, 3]::integer[]`.
 */
final class ArrayValue implements ExpressionInterface
{
    /**
     * @param iterable|LazyArrayInterface|QueryInterface|string|null $value The array value which can be represented as
     * - an `array` of values;
     * - an instance of {@see Traversable} or {@see LazyArrayInterface} that represents an array of values;
     * - an instance of {@see QueryInterface} that represents an SQL sub-query;
     * - a `string` retrieved value from the database that can be parsed into an array;
     * - `null`.
     * @param ColumnInterface|string|null $type The array column type which can be represented as
     * - a native database column type;
     * - an {@see ColumnType abstract} type;
     * - an instance of {@see ColumnInterface};
     * - `null` if the type isn't explicitly specified.
     *
     * String type will be converted into {@see ColumnInterface} using {@see ColumnFactoryInterface::fromDefinition()}.
     * The column type is used to typecast array values before saving into the database and for adding type hint to
     * the SQL statement. If the type isn't specified and DBMS can't guess it from the context, SQL error will be raised.
     * The {@see ColumnDefinitionBuilderInterface::buildType()} method will be invoked to convert {@see ColumnInterface}
     * into SQL representation. For example, it will convert `string[]` to `varchar(255)[]` (for PostgresSQL).
     * The preferred way is to use {@see ColumnBuilder} to generate the column type as an instance of
     * {@see ColumnInterface}.
     */
    public function __construct(
        public readonly iterable|LazyArrayInterface|QueryInterface|string|null $value,
        public readonly ColumnInterface|string|null $type = null,
    ) {
    }
}
