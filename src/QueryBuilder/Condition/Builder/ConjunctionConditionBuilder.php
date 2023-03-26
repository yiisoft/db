<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\AbstractConjunctionCondition;
use Yiisoft\Db\QueryBuilder\Condition\AndCondition;
use Yiisoft\Db\QueryBuilder\Condition\Interface\ConjunctionConditionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function count;
use function implode;
use function is_array;
use function reset;

/**
 * Build an object of {@see AbstractConjunctionCondition} into SQL expressions.
 */
class ConjunctionConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see AndCondition} and {@see OrCondition}.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ConjunctionConditionInterface $expression, array &$params = []): string
    {
        /** @psalm-var string[] $parts */
        $parts = $this->buildExpressionsFrom($expression, $params);

        if (empty($parts)) {
            return '';
        }

        if (count($parts) === 1) {
            return reset($parts);
        }

        return '(' . implode(") {$expression->getOperator()} (", $parts) . ')';
    }

    /**
     * Builds expressions, that are stored in `$condition`.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    private function buildExpressionsFrom(ConjunctionConditionInterface $condition, array &$params = []): array
    {
        $parts = [];

        /** @psalm-var array<array-key, array|ExpressionInterface|string> $expressions */
        $expressions = $condition->getExpressions();

        foreach ($expressions as $conditionValue) {
            if (is_array($conditionValue)) {
                $conditionValue = $this->queryBuilder->buildCondition($conditionValue, $params);
            }

            if ($conditionValue instanceof ExpressionInterface) {
                $conditionValue = $this->queryBuilder->buildExpression($conditionValue, $params);
            }

            if ($conditionValue !== '') {
                $parts[] = $conditionValue;
            }
        }

        return $parts;
    }
}
