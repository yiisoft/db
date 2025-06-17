<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function\Builder;

use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;
use Yiisoft\Db\Expression\Function\Shortest;

/**
 * Builds SQL expressions to represent the function which returns the shortest string from a set of operands.
 *
 * @see Shortest
 */
class ShortestBuilder extends MultiOperandFunctionBuilder
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
        $builtSelects = [];
        $operandAlias = $this->queryBuilder->getQuoter()->quoteSimpleColumnName('0');

        foreach ($expression->getOperands() as $operand) {
            $builtSelects[] = $this->buildSelect($operand, $operandAlias, $params);
        }

        $unions = implode(' UNION ALL ', $builtSelects);

        $lengthClause = $this->queryBuilder->buildExpression(new Length($operandAlias));

        return <<<SQL
            (SELECT $operandAlias FROM ($unions) AS t ORDER BY $lengthClause ASC LIMIT 1)
            SQL;
    }

    protected function buildSelect(mixed $operand, string $alias, array &$params): string
    {
        return 'SELECT ' . $this->buildOperand($operand, $params) . " $alias";
    }
}
