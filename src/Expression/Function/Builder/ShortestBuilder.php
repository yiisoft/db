<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function\Builder;

use Yiisoft\Db\Expression\Function\MultiOperandFunction;
use Yiisoft\Db\Expression\Function\Shortest;

/**
 * Builds SQL representation of function expressions which return the shortest string from a set of operands.
 *
 * ```SQL
 * (SELECT value FROM (
 *     SELECT "column1" AS value
 *     UNION
 *     SELECT "column2" AS value
 * ) AS t ORDER BY LENGTH(value) ASC LIMIT 1)
 * ```
 *
 * @extends MultiOperandFunctionBuilder<Shortest>
 */
final class ShortestBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL expression to represent the function which returns the shortest string.
     *
     * @param Shortest $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $selects = [];

        foreach ($expression->getOperands() as $operand) {
            $selects[] = 'SELECT ' . $this->buildOperand($operand, $params) . ' AS value';
        }

        $unions = implode(' UNION ', $selects);

        return "(SELECT value FROM ($unions) AS t ORDER BY LENGTH(value) ASC LIMIT 1)";
    }
}
