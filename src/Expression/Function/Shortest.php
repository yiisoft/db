<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

use Yiisoft\Db\Expression\Function\Builder\ShortestBuilder;

/**
 * Represents SQL expression that returns the shortest string from a list of operands.
 *
 * This function compares the lengths of the provided strings or expressions and returns the shortest one.
 * If multiple strings have the same minimum length, it returns the first one encountered.
 *
 * Example usage:
 *
 * ```php
 * $shortest = new Shortest(new Value('short'), 'column_name');
 * ```
 *
 * @see ShortestBuilder for building SQL representations of this function expression.
 */
final class Shortest extends MultiOperandFunction {}
