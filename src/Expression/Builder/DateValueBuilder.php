<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Builder;

use Yiisoft\Db\Expression\DateValue;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builder for {@see DateValue} expressions.
 *
 * @implements ExpressionBuilderInterface<DateValue>
 */
final class DateValueBuilder implements ExpressionBuilderInterface
{
    /**
     * @param QueryBuilderInterface $queryBuilder The query builder instance.
     */
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->queryBuilder->bindParam(
            $expression->value->format('Y-m-d'),
            $params,
        );
    }
}
