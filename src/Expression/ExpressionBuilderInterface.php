<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * This interface defines the methods to build database expressions, such as conditions for a SELECT statement or values
 * to insert into a table.
 *
 * These methods include creating comparison operators (such as `=`, `>`, `<`), combining expressions with logical
 * operators (such as `AND`, `OR`), and building sub-queries.
 *
 * The interface provides a consistent way for developers to build expressions for various types of
 * database queries, without having to worry about the specific syntax of the underlying database.
 *
 * @see ExpressionInterface
 */
interface ExpressionBuilderInterface
{
}
