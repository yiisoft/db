<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\ExistConditionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Build an object of {@see ExistsCondition} into SQL expressions.
 */
class ExistsConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see ExistsCondition}.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        if (!$expression instanceof ExistConditionInterface) {
            throw new InvalidConfigException('ExistsConditionBuilder can only be used with ExistConditionInterface instance.');
        }

        $operator = $expression->getOperator();
        $query = $expression->getQuery();
        $sql = $this->queryBuilder->buildExpression($query, $params);
        return "$operator $sql";
    }
}
