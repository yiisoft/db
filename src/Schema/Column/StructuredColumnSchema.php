<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Syntax\JsonParser;
use Yiisoft\Db\Syntax\StructuredParserInterface;

/**
 * Represents the schema for a structured column.
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

    public function dbTypecast(mixed $value): mixed
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        /** @psalm-suppress MixedArgument */
        return new StructuredExpression($value, $this->getDbType(), $this->columns);
    }

    public function phpTypecast(mixed $value): array|null|StructuredExpression
    {
        if ($value === null) {
            return null;
        }

        /** @psalm-suppress MixedArgument */
        return new StructuredExpression($value, $this->getDbType(), $this->columns, $this->getParser());
    }

    /**
     * The parser that will be used to parse values fetched from the database.
     */
    protected function getParser(): StructuredParserInterface
    {
        return new JsonParser();
    }
}
