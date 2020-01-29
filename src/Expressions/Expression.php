<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expressions;

use Yiisoft\Db\Contracts\ExpressionInterface;

/**
 * Expression represents a DB expression that does not need escaping or quoting.
 *
 * When an Expression object is embedded within a SQL statement or fragment, it will be replaced with the
 * {@see expression} property value without any DB escaping or quoting. For example,
 *
 * ```php
 * $expression = new Expression('NOW()');
 * $now = (new \Yiisoft\Db\Query)->select($expression)->scalar();  // SELECT NOW();
 * echo $now; // prints the current date
 * ```
 *
 * Expression objects are mainly created for passing raw SQL expressions to methods of
 * {@see Query}, {@see ActiveQuery}, and related classes.
 */
class Expression implements ExpressionInterface
{
    /**
     * @var string the DB expression
     */
    public $expression;

    /**
     * @var array list of parameters that should be bound for this expression.
     * The keys are placeholders appearing in {@see expression} and the values are the corresponding parameter values.
     */
    public $params = [];

    /**
     * Constructor.
     *
     * @param string $expression the DB expression
     * @param array $params parameters
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($expression, $params = [])
    {
        $this->expression = $expression;
        $this->params = $params;
    }

    /**
     * String magic method.
     *
     * @return string the DB expression.
     */
    public function __toString()
    {
        return $this->expression;
    }
}
