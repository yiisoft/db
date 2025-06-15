<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

use Yiisoft\Db\Expression\ExpressionInterface;

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
 */
final class Least extends MultiOperandFunction
{
    public function addOperand(float|int|string|ExpressionInterface $operand): static
    {
        $this->operands[] = $operand;
        return $this;
    }
}
