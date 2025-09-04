<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * Represents a composite expression consisting of multiple expressions.
 */
final class CompositeExpression implements ExpressionInterface
{
    /**
     * @psalm-var list<string|ExpressionInterface>
     */
    public readonly array $expressions;

    /**
     * @param ExpressionInterface|string ...$expressions The expressions to be combined. String values are treated as
     * a DB expression that doesn't need escaping or quoting.
     *
     * @no-named-arguments
     */
    public function __construct(string|ExpressionInterface ...$expressions)
    {
        $this->expressions = $expressions;
    }
}
