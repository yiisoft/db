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
use function is_string;

class StructuredColumnSchema extends AbstractColumnSchema
{
    /**
     * @var ColumnSchemaInterface[] Columns metadata of the composite type.
     * @psalm-var array<string, ColumnSchemaInterface>
     */
    private array $columns = [];

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
        string $type = ColumnType::STRUCTURED,
    ) {
        parent::__construct($type);
    }

    /**
     * Set columns of the composite type.
     *
     * @param ColumnSchemaInterface[] $columns The metadata of the composite type columns.
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Get the metadata of the composite type columns.
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
