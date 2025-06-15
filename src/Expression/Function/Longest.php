<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

/**
 * Represents SQL expression that returns the longest string from a list of operands.
 *
 * This function compares the lengths of the provided strings or expressions and returns the longest one.
 * If multiple strings have the same maximum length, it returns the first one encountered.
 *
 * Example usage:
 *
 * ```php
 * $longest = new Longest('short', 'longer', 'longest');
 * ```
 *
 * ```sql
 * CASE GREATEST(LENGTH('short'), LENGTH('longer'), LENGTH('longest'))
 *     WHEN LENGTH('short') THEN 'short'
 *     WHEN LENGTH('longer') THEN 'longer'
 *     ELSE 'longest'
 * END
 * ```
 */
final class Longest extends MultiOperandFunction
{
}
