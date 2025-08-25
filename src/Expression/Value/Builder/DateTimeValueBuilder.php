<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\Value\DateTimeValue;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;

/**
 * Builder for {@see DateTimeValue} expressions.
 *
 * @implements ExpressionBuilderInterface<DateTimeValue>
 *
 * @psalm-import-type ColumnInfo from ColumnFactoryInterface
 */
final class DateTimeValueBuilder implements ExpressionBuilderInterface
{
    private ColumnFactoryInterface $columnFactory;

    /**
     * @param QueryBuilderInterface $queryBuilder The query builder instance.
     */
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
        $this->columnFactory = $this->queryBuilder->getColumnFactory();
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $value = $this->columnFactory
            ->fromType($this->prepareType($expression), $this->prepareInfo($expression))
            ->dbTypecast($expression->value);
        return $this->queryBuilder->buildValue($value, $params);
    }

    /**
     * @psalm-return ColumnType::*
     */
    private function prepareType(DateTimeValue $expression): string
    {
        return match ($expression->type) {
            ColumnType::TIMESTAMP,
            ColumnType::TIME,
            ColumnType::TIMETZ,
            ColumnType::DATETIME,
            ColumnType::DATETIMETZ => $expression->type,
            default => ColumnType::TIMESTAMP,
        };
    }

    /**
     * @psalm-return ColumnInfo
     */
    private function prepareInfo(DateTimeValue $expression): array
    {
        $info = ['type' => $expression->type] + $expression->info;

        return match ($expression->type) {
            ColumnType::TIMESTAMP,
            ColumnType::TIME,
            ColumnType::TIMETZ,
            ColumnType::DATETIME,
            ColumnType::DATETIMETZ => $info + ['size' => 0],
            default => $info,
        };
    }
}
