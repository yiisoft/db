<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

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
        return $this->queryBuilder->bindParam(
            $this->columnFactory
                ->fromType($expression->type, ['size' => $expression->size])
                ->dbTypecast($expression->value),
            $params,
        );
    }
}
