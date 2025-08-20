<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Builder;

use DateTimeInterface;
use Yiisoft\Db\Expression\DateTimeValue;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builder for {@see DateTimeValue} expressions.
 *
 * @implements ExpressionBuilderInterface<DateTimeValue>
 */
final class DateTimeValueBuilder implements ExpressionBuilderInterface
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
        $format = $this->hasMicroseconds($expression->value) ? 'Y-m-d H:i:s.u' : 'Y-m-d H:i:s';

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
