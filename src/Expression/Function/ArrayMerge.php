<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * Represents an SQL expression that returns the merged array from a list of operands.
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
    private string|ColumnInterface $type = '';

    /**
     * Returns the type of the operands. Empty string if not set.
     */
    public function getType(): string|ColumnInterface
    {
        return $this->type;
    }

    /**
     * Sets the type of the operands.
     */
    public function type(string|ColumnInterface $type): self
    {
        $this->type = $type;
        return $this;
    }
}
