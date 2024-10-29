<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\StructuredExpression;

use function is_string;
use function json_decode;

/**
 * Represents the schema for a structured column with eager parsing values retrieved from the database.
 *
 * @see StructuredColumnLazySchema for a structured column with lazy parsing values retrieved from the database.
 */
class StructuredColumnSchema extends AbstractColumnSchema
{
    protected const DEFAULT_TYPE = ColumnType::STRUCTURED;

    /**
     * @var ColumnSchemaInterface[] Columns metadata of the structured type.
     * @psalm-var array<string, ColumnSchemaInterface>
     */
    protected array $columns = [];

    /**
     * Set columns of the structured type.
     *
     * @param ColumnSchemaInterface[] $columns The metadata of the structured type columns.
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Get the metadata of the structured type columns.
     *
     * @return ColumnSchemaInterface[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getPhpType(): string
    {
        return PhpType::ARRAY;
    }

    public function dbTypecast(mixed $value): ExpressionInterface|null
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        /** @psalm-suppress MixedArgument */
        return new StructuredExpression($value, $this->getDbType(), $this->columns);
    }

    /**
     * @param string|null $value The string retrieved value from the database that can be parsed into an array.
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): array|null|object
    {
        if (is_string($value)) {
            /** @var array|null */
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return $value;
    }
}
