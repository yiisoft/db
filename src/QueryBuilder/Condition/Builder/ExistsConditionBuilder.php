<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
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
        $operator = $expression->getOperator();
        $query = $expression->getQuery();
        $sql = $this->queryBuilder->buildExpression($query, $params);
        return "$operator $sql";
    }
}
