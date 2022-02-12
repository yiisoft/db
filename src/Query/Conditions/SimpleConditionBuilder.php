<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryBuilderInterface;

use function is_string;
use function strpos;

/**
 * Class NotConditionBuilder builds objects of {@see SimpleCondition}.
 */
class SimpleConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = $expression->getOperator();
        $column = $expression->getColumn();
        $value = $expression->getValue();

        if ($column instanceof ExpressionInterface) {
            $column = $this->queryBuilder->buildExpression($column, $params);
        } elseif (is_string($column) && strpos($column, '(') === false) {
            $column = $this->queryBuilder->quoter()->quoteColumnName($column);
        }

        if ($value === null) {
            return "$column $operator NULL";
        }

        if ($value instanceof ExpressionInterface) {
            return "$column $operator {$this->queryBuilder->buildExpression($value, $params)}";
        }

        $phName = $this->queryBuilder->bindParam($value, $params);

        return "$column $operator $phName";
    }
}
