<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use DateTimeInterface;
use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\Value\DateTimeType;
use Yiisoft\Db\Expression\Value\DateTimeValue;
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
        $format = match ($expression->type) {
            DateTimeType::DateTimeTz => $this->hasMicroseconds($expression->value) ? 'Y-m-d H:i:s.uP' : 'Y-m-d H:i:sP',
            DateTimeType::DateTime => $this->hasMicroseconds($expression->value) ? 'Y-m-d H:i:s.u' : 'Y-m-d H:i:s',
            DateTimeType::Date => 'Y-m-d',
            DateTimeType::TimeTz => $this->hasMicroseconds($expression->value) ? 'H:i:s.uP' : 'H:i:sP',
            DateTimeType::Time => $this->hasMicroseconds($expression->value) ? 'H:i:s.u' : 'H:i:s',
            DateTimeType::Timestamp => 'U',
        };

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
