<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use JsonSerializable;
use Traversable;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Syntax\StructuredParserInterface;

use function array_keys;
use function get_object_vars;
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
     * @param array|object|string $value The content of the structured type. It can be represented as
     * - an associative `array` of column names and values;
     * - an indexed `array` of column values in the order of structured type columns;
     * - an {@see JsonSerializable} object that can be converted to an `array` using `jsonSerialize()`;
     * - an `iterable` object that can be converted to an `array` using `iterator_to_array()`;
     * - an `object` that can be converted to an `array` using `get_object_vars()`;
     * - an {@see ExpressionInterface} object that represents a SQL expression;
     * - a `string` retrieved value from the database that can be parsed into an array.
     * @param string|null $type The structured database type name. Defaults to `null` which means the type is not
     * explicitly specified. Note that in the case where a type is not specified explicitly and DBMS cannot guess it
     * from the context, SQL error will be raised.
     * @param ColumnSchemaInterface[] $columns The structured type columns that are used for value normalization and type
     * casting.
     *
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     */
    public function __construct(
        private readonly array|object|string $value,
        private readonly string|null $type = null,
        private readonly array $columns = [],
        private readonly StructuredParserInterface|null $parser = null
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
     *  - an `ExpressionInterface` object that represents a SQL expression.
     */
    public function getValue(): array|object|string
    {
        return $this->value;
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

        throw new InvalidConfigException('The StructuredExpression value cannot be converted to array.');
    }

    private function parse(string $value): array
    {
        if ($this->parser === null) {
            throw new InvalidConfigException('The StructuredExpression parser must be set to parse the string value.');
        }

        $parsed = $this->parser->parse($value);

        if ($parsed === null) {
            throw new InvalidConfigException('The StructuredExpression value cannot be parsed into array.');
        }

        return $parsed;
    }

    private function phpTypecast(array $value): array
    {
        if (empty($this->columns)) {
            return $value;
        }

        $fields = [];
        $columnNames = array_keys($this->columns);

        /** @psalm-var int|string $columnName */
        foreach ($value as $columnName => $item) {
            $columnName = $columnNames[$columnName] ?? $columnName;

            if (isset($this->columns[$columnName])) {
                $fields[$columnName] = $this->columns[$columnName]->phpTypecast($item);
            } else {
                $fields[$columnName] = $item;
            }
        }

        return $fields;
    }
}
