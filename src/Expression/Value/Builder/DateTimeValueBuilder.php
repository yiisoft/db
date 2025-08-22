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
            DateTimeType::Timestamp,
            DateTimeType::DateTime => 'Y-m-d H:i:s' . $this->getMillisecondsFormat($expression->size),
            DateTimeType::DateTimeTz => 'Y-m-d H:i:s' . $this->getMillisecondsFormat($expression->size) . 'P',
            DateTimeType::Time => 'H:i:s' . $this->getMillisecondsFormat($expression->size),
            DateTimeType::TimeTz => 'H:i:s' . $this->getMillisecondsFormat($expression->size) . 'P',
            DateTimeType::Date => 'Y-m-d',
            DateTimeType::Integer => 'U',
            DateTimeType::Float => 'U.u',
        };

        return $this->queryBuilder->bindParam(
            $expression->value->format($format),
            $params,
        );
    }

    protected function getMillisecondsFormat(int|null $size): string
    {
        return match ($size) {
            0 => '',
            1, 2, 3 => '.v',
            default => '.u',
        };
    }
}
