<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * This interface defines a set of methods that an object should implement to represent an expression in database
 * queries, such as one for filtering or ordering results.
 *
 * These methods include getting the expression as a string, getting the parameters to bind to the expression, and
 * getting the types of the parameters.
 * You can use classes that implement this interface in a variety of query building
 * methods provided by the library.
 *
 * The database abstraction layer of a Yii framework supports objects that implement this interface and will use
 * {@see ExpressionBuilderInterface} to build them.
 *
 * The default implementation is a class {@see Expression}.
 */
interface ExpressionInterface
{
}
