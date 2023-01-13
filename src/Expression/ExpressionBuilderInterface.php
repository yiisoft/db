<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * The ExpressionBuilderInterface class, it defines the methods that classes implementing the interface must implement
 * in order to build database expressions, such as conditions for a SELECT statement or values to be inserted into a
 * table. These methods include things like creating comparison operators (e.g. "=", ">", "<"), combining expressions
 * with logical operators (e.g. "AND", "OR"), and building sub-queries. The interface provides a consistent way for
 * developers to build expressions that can be used in different types of database queries, without having to worry
 * about the specific syntax of the underlying database.
 *
 * {@see ExpressionInterface}.
 */
interface ExpressionBuilderInterface
{
}
