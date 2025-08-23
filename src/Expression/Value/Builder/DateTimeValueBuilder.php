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
            ->fromType($expression->type, $this->prepareInfo($expression))
            ->dbTypecast($expression->value);
        return $this->queryBuilder->buildValue($value, $params);
    }

    private function prepareInfo(DateTimeValue $expression): array
    {
        if ($expression->info !== null) {
            return $expression->info;
        }

        return match ($expression->type) {
            ColumnType::TIMESTAMP,
            ColumnType::TIME,
            ColumnType::TIMETZ,
            ColumnType::DATETIME,
            ColumnType::DATETIMETZ => ['size' => 0],
            ColumnType::DECIMAL => $this->hasMicroseconds($expression)
                ? ['size' => 16, 'scale' => 6]
                : ['size' => 10, 'scale' => 0],
            default => [],
        };
    }

    private function hasMicroseconds(DateTimeValue $expression): bool
    {
        return (int) $expression->value->format('u') > 0;
    }
}
