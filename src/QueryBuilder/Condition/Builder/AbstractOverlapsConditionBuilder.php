<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * The base class for classes building SQL expressions for array and JSON overlaps conditions.
 */
abstract class AbstractOverlapsConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(protected QueryBuilderInterface $queryBuilder)
    {
    }

    protected function prepareColumn(ExpressionInterface|string $column): string
    {
        if ($column instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($column);
        }

        return $this->queryBuilder->getQuoter()->quoteColumnName($column);
    }
}
