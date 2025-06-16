<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Query\QueryInterface;

/**
 * Represents the condition and the result of a WHEN clause in a SQL CASE statement.
 *
 * @see CaseExpression
 */
final class WhenClause
{
    /**
     * @param array|bool|ExpressionInterface|float|int|string $condition The condition for the WHEN clause:
     * - `string` is treated as a SQL expression;
     * - `bool`, `float`, `int`, and `null` are treated as literal values;
     * - `array` is treated as a condition to check, see {@see QueryInterface::where()};
     * - `ExpressionInterface` is treated as an expression to build SQL expression.
     * @param bool|ExpressionInterface|float|int|string|null $result The result to return if the condition is `true`:
     * - `string` is treated as a SQL expression;
     * - `bool`, `float`, `int`, and `null` are treated as literal values;
     * - `ExpressionInterface` is treated as an expression to build SQL expression.
     */
    public function __construct(
        public readonly array|bool|ExpressionInterface|float|int|string $condition,
        public readonly bool|ExpressionInterface|float|int|string|null $result,
    ) {
    }
}
