<?php
declare(strict_types=1);

namespace Yiisoft\Db\Conditions;

use Yiisoft\Db\ExpressionBuilderInterface;
use Yiisoft\Db\ExpressionBuilderTrait;
use Yiisoft\Db\ExpressionInterface;

/**
 * Class ConjunctionConditionBuilder builds objects of abstract class [[ConjunctionCondition]].
 */
class ConjunctionConditionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * Method builds the raw SQL from the $expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface|ConjunctionCondition $condition the expression to be built.
     * @param array $params the binding parameters.
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $condition, array &$params = [])
    {
        $parts = $this->buildExpressionsFrom($condition, $params);

        if (empty($parts)) {
            return '';
        }

        if (count($parts) === 1) {
            return reset($parts);
        }

        return '('.implode(") {$condition->getOperator()} (", $parts).')';
    }

    /**
     * Builds expressions, that are stored in $condition.
     *
     * @param ExpressionInterface|ConjunctionCondition $condition the expression to be built.
     * @param array $params the binding parameters.
     *
     * @return string[]
     */
    private function buildExpressionsFrom(ExpressionInterface $condition, &$params = [])
    {
        $parts = [];
        foreach ($condition->getExpressions() as $condition) {
            if (is_array($condition)) {
                $condition = $this->queryBuilder->buildCondition($condition, $params);
            }
            if ($condition instanceof ExpressionInterface) {
                $condition = $this->queryBuilder->buildExpression($condition, $params);
            }
            if ($condition !== '') {
                $parts[] = $condition;
            }
        }

        return $parts;
    }
}
