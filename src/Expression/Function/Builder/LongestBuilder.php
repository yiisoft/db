<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function\Builder;

use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;

/**
 * Builds SQL expressions to represent the function which returns the longest string from a set of operands.
 *
 * @see Longest
 */
class LongestBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL expression to represent the function which returns the longest string.
     *
     * @param Greatest $expression The expression to build.
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
            (SELECT $operandAlias FROM ($unions) AS t ORDER BY $lengthClause DESC LIMIT 1)
            SQL;
    }

    protected function buildSelect(mixed $operand, string $alias, array &$params): string
    {
        return 'SELECT ' . $this->buildOperand($operand, $params) . " $alias";
    }
}
