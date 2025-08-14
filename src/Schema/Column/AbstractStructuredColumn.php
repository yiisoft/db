<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\StructuredExpression;

/**
 * Represents an abstract structured column.
 *
 * @see StructuredColumn for a structured column with eager parsing values retrieved from the database.
 * @see StructuredLazyColumn for a structured column with lazy parsing values retrieved from the database.
 */
abstract class AbstractStructuredColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::STRUCTURED;

    /**
     * @var ColumnInterface[] Columns metadata of the structured type.
     * @psalm-var array<string, ColumnInterface>
     */
    protected array $columns = [];

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
     * @psalm-return array<string, ColumnInterface>
     * @psalm-mutation-free
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array|object|string|null $value
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function dbTypecast(mixed $value): ExpressionInterface|null
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        return new StructuredExpression($value, $this);
    }
}
