<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\NotConditionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Build an object of {@see NotCondition} into SQL expressions.
 */
class NotConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see NotCondition}.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(NotConditionInterface $expression, array &$params = []): string
    {
        $operand = $expression->getCondition();

        if ($operand === '') {
            return '';
        }

        $expressionValue = $this->queryBuilder->buildCondition($operand, $params);

        return "{$this->getNegationOperator()} ($expressionValue)";
    }

    protected function getNegationOperator(): string
    {
        return 'NOT';
    }
}
