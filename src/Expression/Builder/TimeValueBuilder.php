<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Builder;

use DateTimeInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\TimeValue;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builder for {@see TimeValue} expressions.
 *
 * @implements ExpressionBuilderInterface<TimeValue>
 */
final class TimeValueBuilder implements ExpressionBuilderInterface
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
        $format = $this->hasMicroseconds($expression->value) ? 'H:i:s.u' : 'H:i:s';

        return $this->queryBuilder->bindParam(
            $expression->value->format($format),
            $params,
        );
    }

    private function hasMicroseconds(DateTimeInterface $value): bool
    {
        return (int) $value->format('u') > 0;
    }
}
