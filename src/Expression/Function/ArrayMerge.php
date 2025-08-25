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
    private bool $ordered = false;
    private string|ColumnInterface $type = '';

    /**
     * Returns whether the result array should be ordered.
     */
    public function getOrdered(): bool
    {
        return $this->ordered;
    }

    /**
     * Returns the type of the operands. Empty string if not set.
     */
    public function getType(): string|ColumnInterface
    {
        return $this->type;
    }

    /**
     * Sets whether the result array should be ordered.
     */
    public function ordered(bool $ordered = true): self
    {
        $this->ordered = $ordered;
        return $this;
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
