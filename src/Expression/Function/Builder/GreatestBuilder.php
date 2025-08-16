<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function\Builder;

use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;

use function implode;

/**
 * Builds SQL GREATEST() function expressions for {@see Greatest} objects.
 */
final class GreatestBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL GREATEST() function expression from the given {@see Greatest} object.
     *
     * @param Greatest $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL GREATEST() function expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $builtOperands = [];

        foreach ($expression->getOperands() as $operand) {
            $builtOperands[] = $this->buildOperand($operand, $params);
        }

        return 'GREATEST(' . implode(', ', $builtOperands) . ')';
    }
}
