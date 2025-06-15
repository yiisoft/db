<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

/**
 * Represents SQL expression that returns the shortest string from a list of operands.
 *
 * This function compares the lengths of the provided strings or expressions and returns the shortest one.
 * If multiple strings have the same minimum length, it returns the first one encountered.
 *
 * Example usage:
 *
 * ```php
 * $shortest = new Shortest('short', 'longer', 'longest');
 * ```
 *
 * ```sql
 * CASE LEAST(LENGTH('short'), LENGTH('longer'), LENGTH('longest'))
 *     WHEN LENGTH('short') THEN 'short'
 *     WHEN LENGTH('longer') THEN 'longer'
 *     ELSE 'longest'
 * END
 * ```
 */
final class Shortest extends MultiOperandFunction
{
}
