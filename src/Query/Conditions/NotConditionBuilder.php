<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\Interface\NotConditionBuilderInterface;
use Yiisoft\Db\Query\Conditions\Interface\NotConditionInterface;
use Yiisoft\Db\Query\QueryBuilderInterface;

/**
 * Class NotConditionBuilder builds objects of {@see NotCondition}.
 */
class NotConditionBuilder implements NotConditionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    public function build(NotConditionInterface $expression, array &$params = []): string
    {
        $operand = $expression->getCondition();

        if ($operand === '') {
            return '';
        }

        $expession = $this->queryBuilder->buildCondition($operand, $params);

        return "{$this->getNegationOperator()} ($expession)";
    }

    protected function getNegationOperator(): string
    {
        return 'NOT';
    }
}
