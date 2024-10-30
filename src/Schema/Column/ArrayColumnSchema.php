<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionInterface;

use Yiisoft\Db\Schema\Data\LazyArrayInterface;

use function is_iterable;

/**
 * Represents the schema for an array column with eager parsing values retrieved from the database.
 *
 * @see ArrayColumnLazySchema for an array column with lazy parsing values retrieved from the database.
 */
class ArrayColumnSchema extends AbstractColumnSchema
{
    protected const DEFAULT_TYPE = ColumnType::ARRAY;

    /**
     * @var ColumnSchemaInterface|null The column of an array item.
     */
    protected ColumnSchemaInterface|null $column = null;

    /**
     * @var int The dimension of array, must be greater than 0.
     * @psalm-var positive-int
     */
    protected int $dimension = 1;

    /**
     * Set column of an array item.
     */
    public function column(ColumnSchemaInterface|null $column): static
    {
        $this->column = $column;
        return $this;
    }

    /**
     * @return ColumnSchemaInterface the column of an array item.
     */
    public function getColumn(): ColumnSchemaInterface
    {
        if ($this->column === null) {
            $this->column = new StringColumnSchema();
            $this->column->dbType($this->getDbType());
            $this->column->enumValues($this->getEnumValues());
            $this->column->scale($this->getScale());
            $this->column->size($this->getSize());
        }

        return $this->column;
    }

    /**
     * Set dimension of an array, must be greater than
     *
     * @psalm-param positive-int $dimension
     */
    public function dimension(int $dimension): static
    {
        $this->dimension = $dimension;
        return $this;
    }

    /**
     * @return int the dimension of the array.
     *
     * @psalm-return positive-int
     */
    public function getDimension(): int
    {
        return $this->dimension;
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

        if (!is_iterable($value)) {
            return null;
        }

        $column = $this->getColumn();

        return new ArrayExpression(
            $value,
            $this->getDbType() ?? $column->getDbType(),
            $this->dimension,
            $column
        );
    }

    /**
     * @param string|null $value The string retrieved value from the database that can be parsed into an array.
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function phpTypecast(mixed $value): array|LazyArrayInterface|null
    {
        if (is_string($value)) {
            /** @var array|null */
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return $value;
    }
}
