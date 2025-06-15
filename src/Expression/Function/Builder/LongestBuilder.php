<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function\Builder;

use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;

use function array_map;
use function array_pop;

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
        $lengths = array_map(
            static fn ($operand): Length => new Length($operand),
            $expression->getOperands(),
        );

        $case = (new CaseExpression(new Greatest(...$lengths)))
            ->else(array_pop($lengths)->operand);

        foreach ($lengths as $length) {
            $case->addWhen($length, $length->operand);
        }

        return $this->queryBuilder->buildExpression($case, $params);
    }
}
