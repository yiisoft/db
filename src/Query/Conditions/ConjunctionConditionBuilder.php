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

use function count;
use function implode;
use function is_array;
use function reset;

/**
 * Class ConjunctionConditionBuilder builds objects of abstract class {@see ConjunctionCondition}.
 */
class ConjunctionConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
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
     * Builds expressions, that are stored in $condition.
     *
     * @param ConjunctionCondition|ExpressionInterface $condition the expression to be built.
     * @param array $params the binding parameters.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array
     */
    private function buildExpressionsFrom(ExpressionInterface $condition, array &$params = []): array
    {
        $parts = [];

        foreach ($condition->getExpressions() as $conditionValue) {
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
