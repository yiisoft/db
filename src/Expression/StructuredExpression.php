<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Traversable;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use function array_key_exists;
use function array_keys;
use function is_iterable;
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
     * @param ColumnSchemaInterface[] $columns
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     */
    public function __construct(
        private mixed $value,
        private string|null $type = null,
        private array $columns = [],
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
     * The content of the structured type. It can be represented as an associative array of structured type column names
     * and values.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Sorted values according to the order of structured type columns,
     * indexed keys are replaced with column names,
     * missing elements are filled in with default values,
     * excessive elements are removed.
     */
    public function getNormalizedValue(): mixed
    {
        if (empty($this->columns) || !is_iterable($this->value)) {
            return $this->value;
        }

        $normalized = [];
        $value = $this->value;
        $columnsNames = array_keys($this->columns);

        if ($value instanceof Traversable) {
            $value = iterator_to_array($value);
        }

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
