<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Builder\LengthBuilder;

/**
 * Represents a SQL LENGTH() function that returns the length of string represented as an expression.
 *
 * Example usage:
 *
 * ```php
 * $length = new Length('expression');
 * ```
 *
 * ```sql
 * LENGTH(expression)
 * ```
 *
 * @see LengthBuilder for building SQL representations of this function expression.
 */
final class Length implements ExpressionInterface
{
    /**
     * @param ExpressionInterface|string $operand The expression for which to calculate the length.
     */
    public function __construct(
        public readonly string|ExpressionInterface $operand
    ) {
    }
}
