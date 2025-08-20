<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Builder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\TimestampValue;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builder for {@see TimestampValue} expressions.
 *
 * @implements ExpressionBuilderInterface<TimestampValue>
 */
final class TimestampValueBuilder implements ExpressionBuilderInterface
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
        return (string) $expression->value->getTimestamp();
    }
}
