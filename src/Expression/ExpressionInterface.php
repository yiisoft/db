<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * The ExpressionInterface defines a set of methods that an object should implement in order to be used as an expression
 * in database queries, such as for filtering or ordering results. These methods include getting the expression as a
 * string, getting the parameters to bind to the expression, and getting the types of the parameters. Classes that
 * implement this interface can be used in a variety of query building methods provided by the library.
 *
 * The database abstraction layer of Yii framework supports objects that implement this interface and will use
 * {@see ExpressionBuilderInterface} to build them.
 *
 * The default implementation is a class {@see Expression}.
 */
interface ExpressionInterface
{
}
