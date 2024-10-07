<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Traversable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Syntax\ParserToArrayInterface;

use function array_map;
use function array_walk_recursive;
use function is_array;
use function is_iterable;
use function is_string;
use function iterator_to_array;

class ArrayColumnSchema extends AbstractColumnSchema
{
    /**
     * @var ColumnSchemaInterface|null The column of an array item.
     */
    private ColumnSchemaInterface|null $column = null;

    /**
     * @var int The dimension of array, must be greater than 0.
     */
    private int $dimension = 1;

    /**
     * Returns the parser for the column value.
     */
    protected function getParser(): ParserToArrayInterface
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported. Use concrete DBMS implementation.');
    }

    /**
     * @psalm-param ColumnType::* $type
     */
    public function __construct(
        string $type = ColumnType::ARRAY,
    ) {
        parent::__construct($type);
    }

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
     * Set dimension of an array, must be greater than 0.
     */
    public function dimension(int $dimension): static
    {
        $this->dimension = $dimension;
        return $this;
    }

    /**
     * @return int the dimension of the array.
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

        if ($this->dimension === 1 && is_array($value)) {
            $value = array_map($this->getColumn()->dbTypecast(...), $value);
        } else {
            $value = $this->dbTypecastArray($value, $this->dimension);
        }

        return new ArrayExpression($value, $this->getDbType() ?? $this->getColumn()->getDbType(), $this->dimension);
    }

    public function phpTypecast(mixed $value): array|null
    {
        if (is_string($value)) {
            $value = $this->getParser()->parse($value);
        }

        if (!is_array($value)) {
            return null;
        }

        if ($this->getType() === ColumnType::STRING) {
            return $value;
        }

        $column = $this->getColumn();

        if ($this->dimension === 1 && $column->getType() !== ColumnType::JSON) {
            return array_map($column->phpTypecast(...), $value);
        }

        array_walk_recursive($value, function (string|null &$val) use ($column): void {
            $val = $column->phpTypecast($val);
        });

        return $value;
    }

    /**
     * Recursively converts array values for use in a db query.
     *
     * @param mixed $value The array or iterable object.
     * @param int $dimension The array dimension. Should be more than 0.
     *
     * @return array|null Converted values.
     */
    protected function dbTypecastArray(mixed $value, int $dimension): array|null
    {
        if ($value === null) {
            return null;
        }

        if (!is_iterable($value)) {
            return [];
        }

        if ($dimension <= 1) {
            return array_map(
                $this->getColumn()->dbTypecast(...),
                $value instanceof Traversable
                    ? iterator_to_array($value, false)
                    : $value
            );
        }

        $items = [];

        foreach ($value as $val) {
            $items[] = $this->dbTypecastArray($val, $dimension - 1);
        }

        return $items;
    }
}
