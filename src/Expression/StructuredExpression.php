<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use JsonSerializable;
use Traversable;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use function array_key_exists;
use function array_keys;
use function get_object_vars;
use function is_object;
use function iterator_to_array;

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
     * - an {@see ExpressionInterface} object that represents a SQL expression.
     * @param string|null $type The structured database type name. Defaults to `null` which means the type is not
     * explicitly specified. Note that in the case where a type is not specified explicitly and DBMS cannot guess it
     * from the context, SQL error will be raised.
     * @param ColumnSchemaInterface[] $columns The structured type columns that are used for value normalization and type
     * casting.
     *
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     */
    public function __construct(
        private readonly array|object $value,
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
     *  - an `ExpressionInterface` object that represents a SQL expression.
     */
    public function getValue(): array|object
    {
        return $this->value;
    }

    /**
     * Returns the normalized value of the structured type, where:
     * - values sorted according to the order of structured type columns;
     * - indexed keys are replaced with column names;
     * - missing elements are filled in with default values;
     * - excessive elements are removed.
     *
     * If the structured type columns are not specified or the value is an `ExpressionInterface` object,
     * it will be returned as is.
     */
    public function getNormalizedValue(): array|object
    {
        $value = $this->value;

        if (empty($this->columns) || $value instanceof ExpressionInterface) {
            return $value;
        }

        if (is_object($value)) {
            if ($value instanceof JsonSerializable) {
                /** @var array */
                $value = $value->jsonSerialize();
            } elseif ($value instanceof Traversable) {
                $value = iterator_to_array($value);
            } else {
                $value = get_object_vars($value);
            }
        }

        $normalized = [];
        $columnsNames = array_keys($this->columns);

        foreach ($columnsNames as $i => $columnsName) {
            $normalized[$columnsName] = match (true) {
                array_key_exists($columnsName, $value) => $value[$columnsName],
                array_key_exists($i, $value) => $value[$i],
                default => $this->columns[$columnsName]->getDefaultValue(),
            };
        }

        return $normalized;
    }
}
