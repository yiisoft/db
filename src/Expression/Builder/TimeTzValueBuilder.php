<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Builder;

use DateTimeInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\TimeTzValue;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builder for {@see TimeTzValue} expression.
 *
 * @implements ExpressionBuilderInterface<TimeTzValue>
 */
final class TimeTzValueBuilder implements ExpressionBuilderInterface
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
        $format = $this->hasMicroseconds($expression->value) ? 'H:i:s.uP' : 'H:i:sP';

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
