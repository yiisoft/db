<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Simple;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function str_contains;

/**
 * Build an object of {@see Simple} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Simple>
 */
class SimpleBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder) {}

    /**
     * Build SQL for {@see Simple}.
     *
     * @param Simple $expression
     *
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = $expression->operator;
        $column = $expression->column;
        $value = $expression->value;

        $column = $column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($column, $params)
            : $this->queryBuilder->getQuoter()->quoteColumnName($column);

        if ($value === null) {
            return "$column $operator NULL";
        }

        if ($value instanceof ExpressionInterface) {
            return "$column $operator {$this->queryBuilder->buildExpression($value, $params)}";
        }

        return "$column $operator {$this->queryBuilder->buildValue($value, $params)}";
    }
}
