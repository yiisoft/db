<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Closure;
use JsonSerializable;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

/**
 * Represents a structured type SQL expression.
 *
 * @see https://en.wikipedia.org/wiki/Structured_type
 *
 * For example:
 *
 * ```php
 * new StructuredExpression(['price' => 10, 'currency_code' => 'USD']);
 * ```
 *
 * Will be encoded to `ROW(10, USD)` in PostgreSQL.
 */
final class StructuredExpression implements ExpressionInterface
{
    /**
     * @param array|object $value The content of the structured type. It can be represented as
     * - an associative `array` of column names and values;
     * - an indexed `array` of column values in the order of structured type columns;
     * - an {@see JsonSerializable} object that can be converted to an `array` using `jsonSerialize()`;
     * - an `iterable` object that can be converted to an `array` using `iterator_to_array()`;
     * - an `object` that can be converted to an `array` using `get_object_vars()`;
     * - an {@see QueryInterface} object that represents a SQL sub-query.
     * @param string|null $type The structured database type name. Defaults to `null` which means the type is not
     * explicitly specified. Note that in the case where a type is not specified explicitly and DBMS cannot guess it
     * from the context, SQL error will be raised.
     * @param ColumnSchemaInterface[] $columns The structured type columns that are used for value normalization and type
     * casting.
     *
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     * @psalm-param class-string|Closure(mixed...):object $className
     */
    public function __construct(
        private array|object $value,
        private readonly string|null $type = null,
        private readonly array $columns = [],
    ) {
    }

    /**
     * The structured type name.
     *
     * Defaults to `null` which means the type is not explicitly specified.
     *
     * Note that in the case where a type is not specified explicitly and DBMS cannot guess it from the context,
     * SQL error will be raised.
     */
    public function getType(): string|null
    {
        return $this->type;
    }

    /**
     * The structured type columns that are used for value normalization and type casting.
     *
     * @return ColumnSchemaInterface[]
     *
     * @psalm-return array<string, ColumnSchemaInterface>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * The content of the structured type. It can be represented as
     *  - an associative `array` of column names and values;
     *  - an indexed `array` of column values in the order of structured type columns;
     *  - an `iterable` object that can be converted to an `array` using `iterator_to_array()`;
     *  - an `object` that can be converted to an `array` using `get_object_vars()`;
     *  - an `ExpressionInterface` object that represents a SQL expression;
     * - a `string` retrieved value from the database that can be parsed into an array.
     */
    public function getValue(): array|object|string
    {
        return $this->value;
    }
}
