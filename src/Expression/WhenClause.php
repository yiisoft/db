<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * Represents the condition and the result of a WHEN clause in a SQL CASE statement.
 *
 * @see CaseExpression
 */
final class WhenClause
{
    /**
     * @param array|bool|ExpressionInterface|float|int|string $condition The condition for the WHEN clause.
     * @param bool|ExpressionInterface|float|int|string|null $result The result to return if the condition is `true`.
     */
    public function __construct(
        public readonly array|bool|ExpressionInterface|float|int|string $condition,
        public readonly bool|ExpressionInterface|float|int|string|null $result,
    ) {
    }
}
