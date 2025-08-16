<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

use Yiisoft\Db\Expression\Function\Builder\LeastBuilder;

/**
 * Represents a SQL LEAST() function that returns the least value from a list of values or expressions.
 *
 * Example usage:
 *
 * ```php
 * $least = new Least(1, 'a + b', $db->select('column')->from('table')->where(['id' => 1]));
 * ```
 *
 * ```sql
 * LEAST(1, a + b, (SELECT "column" FROM "table" WHERE "id" = 1))
 * ```
 *
 * @see LeastBuilder for building SQL representations of this function expression.
 */
final class Least extends MultiOperandFunction
{
}
