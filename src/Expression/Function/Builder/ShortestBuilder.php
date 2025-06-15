<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function\Builder;

use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\Function\Least;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;
use Yiisoft\Db\Expression\Function\Shortest;

use function array_map;
use function array_pop;

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
        $lengths = array_map(
            static fn ($operand): Length => new Length($operand),
            $expression->getOperands(),
        );

        $case = (new CaseExpression(new Least(...$lengths)))
            ->else(array_pop($lengths)->operand);

        foreach ($lengths as $length) {
            $case->addWhen($length, $length->operand);
        }

        return $this->queryBuilder->buildExpression($case, $params);
    }
}
