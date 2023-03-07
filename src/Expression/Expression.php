<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Stringable;

/**
 * Represents a DB expression that doesn't need escaping or quoting.
 *
 * When an Expression object is embedded within a SQL statement or fragment, it will be replaced with the
 * {@see expression} property value without any DB escaping or quoting. For example,
 *
 * ```php
 * $expression = new Expression('NOW()');
 * $now = (new \Yiisoft\Db\Query\Query)->select($expression)->scalar();  // SELECT NOW();
 * echo $now; // prints the current date
 * ```
 *
 * Expression objects are mainly created for passing raw SQL expressions to methods of
 * {@see \Yiisoft\Db\Query\QueryInterface} and related classes.
 */
class Expression implements ExpressionInterface, Stringable
{
    public function __construct(private string $expression, private array $params = [])
    {
    }

    /**
     * @return string The expression.
     */
    public function __toString(): string
    {
        return $this->expression;
    }

    /**
     * @return array List of parameters that should be bound for this expression. The keys are placeholders appearing in
     * {@see expression} and the values are the corresponding parameter values.
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
