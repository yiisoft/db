<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents a SQL GREATEST() function that returns the greatest value from a list of values or expressions.
 *
 * Example usage:
 *
 * ```php
 * $greatest = new Greatest(1, 'a + b', $db->select('column')->from('table')->where(['id' => 1]));
 * ```
 *
 * ```sql
 * GREATEST(1, a + b, (SELECT "column" FROM "table" WHERE "id" = 1))
 * ```
 */
final class Greatest extends MultiOperandFunction
{
    public function addOperand(float|int|string|ExpressionInterface $operand): static
    {
        $this->operands[] = $operand;
        return $this;
    }
}
