<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\AbstractCompare;
use Yiisoft\Db\QueryBuilder\Condition\Equals;
use Yiisoft\Db\QueryBuilder\Condition\GreaterThan;
use Yiisoft\Db\QueryBuilder\Condition\GreaterThanOrEqual;
use Yiisoft\Db\QueryBuilder\Condition\LessThan;
use Yiisoft\Db\QueryBuilder\Condition\LessThanOrEqual;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Build objects of {@see Equals}, {@see GreaterThan}, {@see GreaterThanOrEqual}, {@see LessThan},
 * or {@see LessThanOrEqual} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Equals|GreaterThan|GreaterThanOrEqual|LessThan|LessThanOrEqual>
 */
class CompareBuilder implements ExpressionBuilderInterface
{

    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    /**
     * Build SQL for comparison conditions.
     *
     * @param Equals|GreaterThan|GreaterThanOrEqual|LessThan|LessThanOrEqual $expression
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $this->prepareColumn($expression->column, $params);
        $value = $this->prepareValue($expression->value, $params);

        $operator = $this->getOperator($expression);

        if ($operator === '=') {
            return $value === null
                ? "$column IS NULL"
                : "$column = $value";
        }

        return $value === null
            ? '0=1'
            : "$column $operator $value";
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Exception
     */
    private function prepareColumn(string|ExpressionInterface $column, array &$params): string
    {
        if ($column instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($column, $params);
        }

        return $this->queryBuilder->getQuoter()->quoteColumnName($column);
    }

    private function prepareValue(mixed $value, array &$params): string|null
    {
        if ($value === null) {
            return null;
        }

        return $this->queryBuilder->buildValue($value, $params);
    }

    private function getOperator(AbstractCompare $expression): string
    {
        return match ($expression::class) {
            Equals::class => '=',
            GreaterThan::class => '>',
            GreaterThanOrEqual::class => '>=',
            LessThan::class => '<',
            LessThanOrEqual::class => '<=',
        };
    }
}
