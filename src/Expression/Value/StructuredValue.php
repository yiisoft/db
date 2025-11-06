<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use JsonSerializable;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\AbstractStructuredColumn;

/**
 * Represents a structured type SQL expression.
 *
 * @see https://en.wikipedia.org/wiki/Structured_type
 *
 * For example:
 *
 * ```php
 * new StructuredValue(['price' => 10, 'currency_code' => 'USD']);
 * ```
 *
 * Will be encoded to `ROW(10, USD)` in PostgreSQL.
 */
final class StructuredValue implements ExpressionInterface
{
    /**
     * @param array|object|string|null $value The content of the structured type which can be represented as
     * - an associative `array` of column names and values;
     * - an indexed `array` of column values in the order of structured type columns;
     * - an {@see JsonSerializable} object that can be converted to an `array` using `jsonSerialize()`;
     * - an `iterable` object that can be converted to an `array` using `iterator_to_array()`;
     * - an `object` that can be converted to an `array` using `get_object_vars()`;
     * - an {@see QueryInterface} object that represents a SQL sub-query;
     * - a `string` retrieved value from the database that can be parsed into an array;
     * - `null`.
     * @param AbstractStructuredColumn|string|null $type The structured column type which can be represented as
     * - a native database column type suitable to store the {@see value};
     * - an instance of {@see AbstractStructuredColumn};
     * - `null` if the type isn't explicitly specified.
     *
     * The column type is used to typecast structured values before saving into the database and for adding type hint to
     * the SQL statement. If the type isn't specified and DBMS can't guess it from the context, SQL error will be raised.
     */
    public function __construct(
        public readonly array|object|string|null $value,
        public readonly AbstractStructuredColumn|string|null $type = null,
    ) {}
}
