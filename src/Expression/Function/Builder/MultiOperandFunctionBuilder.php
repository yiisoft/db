<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function\Builder;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function count;
use function is_string;

/**
 * Base class for building SQL representation of multi-operand function expressions.
 *
 * @template T as MultiOperandFunction
 * @implements ExpressionBuilderInterface<T>
 */
abstract class MultiOperandFunctionBuilder implements ExpressionBuilderInterface
{
    public function __construct(protected readonly QueryBuilderInterface $queryBuilder) {}

    /**
     * Builds a SQL multi-operand function expression from the given {@see MultiOperandFunction} instance.
     *
     * @param MultiOperandFunction $expression The expression to build.
     * @param array $params The parameters to be bound to the query.
     *
     * @psalm-param T $expression
     *
     * @return string SQL multi-operand function expression.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operands = $expression->getOperands();

        if (empty($operands)) {
            throw new InvalidArgumentException(
                'The ' . $expression::class . ' expression must have at least one operand.',
            );
        }

        if (count($operands) === 1) {
            return '(' . $this->buildOperand($operands[0], $params) . ')';
        }

        return $this->buildFromExpression($expression, $params);
    }

    /**
     * Builds a SQL multi-operand function expression from the given {@see MultiOperandFunction} instance.
     *
     * @param MultiOperandFunction $expression The expression to build from.
     * @param array $params The parameters to be bound to the query.
     *
     * @psalm-param T $expression
     *
     * @return string SQL multi-operand function expression.
     */
    abstract protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string;

    /**
     * Builds an operand expression of the multi-operand function.
     */
    protected function buildOperand(mixed $operand, array &$params): string
    {
        if (is_string($operand)) {
            return $this->queryBuilder->getQuoter()->quoteColumnName($operand);
        }

        return $this->queryBuilder->buildValue($operand, $params);
    }
}
