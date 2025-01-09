<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Syntax\ParserToArrayInterface;

use function array_keys;
use function is_array;
use function is_string;

/**
 * Represents the schema for a structured column.
 */
class StructuredColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::STRUCTURED;

    /**
     * @var ColumnInterface[] Columns metadata of the structured type.
     * @psalm-var array<string, ColumnInterface>
     */
    protected array $columns = [];

    /**
     * Returns the parser for the column value.
     */
    protected function getParser(): ParserToArrayInterface
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported. Use concrete DBMS implementation.');
    }

    /**
     * Set columns of the structured type.
     *
     * @param ColumnInterface[] $columns The metadata of the structured type columns.
     * @psalm-param array<string, ColumnInterface> $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Get the metadata of the structured type columns.
     *
     * @return ColumnInterface[]
     * @psalm-mutation-free
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @psalm-mutation-free */
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

    public function phpTypecast(mixed $value): array|null
    {
        if (is_string($value)) {
            $value = $this->getParser()->parse($value);
        }

        if (!is_array($value)) {
            return null;
        }

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
