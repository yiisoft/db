<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\NotConditionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Class NotConditionBuilder builds objects of {@see NotCondition}.
 */
class NotConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function build(NotConditionInterface $expression, array &$params = []): string
    {
        $operand = $expression->getCondition();

        if ($operand === '') {
            return '';
        }

        $expression = $this->queryBuilder->buildCondition($operand, $params);

        return "{$this->getNegationOperator()} ($expression)";
    }

    protected function getNegationOperator(): string
    {
        return 'NOT';
    }
}
