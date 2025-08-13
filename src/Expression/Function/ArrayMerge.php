<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

/**
 * Represents SQL expression that returns the merged array from a list of operands.
 *
 * Example usage:
 *
 * ```php
 * $arrayMerge = new ArrayMerge('operand1', 'operand2');
 * ```
 *
 * For example, it will be generated into the following SQL expression in PostgreSQL:
 *
 * ```sql
 * ARRAY(SELECT DISTINCT UNNEST(operand1 || operand2))
 * ```
 */
final class ArrayMerge extends MultiOperandFunction
{
}
