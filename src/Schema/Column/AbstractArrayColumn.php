<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;

/**
 * Represents an abstract array column.
 *
 * @see ArrayColumn for an array column with eager parsing values retrieved from the database.
 * @see ArrayLazyColumn for an array column with lazy parsing values retrieved from the database.
 */
abstract class AbstractArrayColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::ARRAY;

    /**
     * @var ColumnInterface|null The column of an array item.
     */
    protected ColumnInterface|null $column = null;

    /**
     * @var int The dimension of array, must be greater than 0.
     * @psalm-var positive-int
     */
    protected int $dimension = 1;

    /**
     * Set column of an array item.
     */
    public function column(ColumnInterface|null $column): static
    {
        $this->column = $column;
        return $this;
    }

    /**
     * @return ColumnInterface|null The column of an array item.
     * @psalm-mutation-free
     */
    public function getColumn(): ColumnInterface|null
    {
        return $this->column;
    }

    /**
     * Set dimension of an array, must be greater than `0`.
     *
     * @psalm-param positive-int $dimension
     */
    public function dimension(int $dimension): static
    {
        $this->dimension = $dimension;
        return $this;
    }

    /**
     * @return int The dimension of the array.
     *
     * @psalm-return positive-int
     * @psalm-mutation-free
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    /** @psalm-mutation-free */
    public function getPhpType(): string
    {
        return PhpType::ARRAY;
    }

    /**
     * @param iterable|LazyArrayInterface|QueryInterface|string|null $value
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function dbTypecast(mixed $value): ExpressionInterface|null
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        return new ArrayExpression($value, $this);
    }
}
