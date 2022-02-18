<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Query\Conditions\Interface\ExistConditionBuilderInterface;
use Yiisoft\Db\Query\Conditions\Interface\ExistConditionInterface;
use Yiisoft\Db\Query\QueryBuilderInterface;

/**
 * Class ExistsConditionBuilder builds objects of {@see ExistsCondition}.
 */
class ExistsConditionBuilder implements ExistConditionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    public function build(ExistConditionInterface $expression, array &$params = []): string
    {
        $operator = $expression->getOperator();
        $query = $expression->getQuery();
        $sql = $this->queryBuilder->buildExpression($query, $params);
        return "$operator $sql";
    }
}
