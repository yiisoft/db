<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * Represents a composite expression consisting of multiple expressions.
 */
final class CompositeExpression implements ExpressionInterface
{
    /**
     * @param (ExpressionInterface|string)[] $expressions The expressions to be combined. String values are treated as a DB expression that
     * doesn't need escaping or quoting.
     * @param string $separator The separator to use when concatenating the expressions. Defaults to a space character.
     *
     * @psalm-param list<string|ExpressionInterface> $expressions
     */
    public function __construct(
        public readonly array $expressions,
        public readonly string $separator = ' ',
    ) {
    }
}
