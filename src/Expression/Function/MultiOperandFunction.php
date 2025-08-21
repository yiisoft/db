<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * Base class for functions that operate on multiple operands with the same type.
 *
 * It provides methods to add operands, retrieve them, and set the type of the operands.
 *
 * @see MultiOperandFunctionBuilder base class for building SQL representation of multi-operand function expressions.
 */
abstract class MultiOperandFunction implements ExpressionInterface
{
    /**
     * @var array List of operands.
     */
    protected array $operands = [];

    private string|ColumnInterface $type = '';

    /**
     * @param mixed ...$operands The values or expressions to operate on.
     */
    public function __construct(mixed ...$operands)
    {
        $this->operands = $operands;
    }

    public function add(mixed $operand): static
    {
        $this->operands[] = $operand;
        return $this;
    }

    /**
     * @return array List of operands.
     */
    public function getOperands(): array
    {
        return $this->operands;
    }

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
    public function type(string|ColumnInterface $type): static
    {
        $this->type = $type;
        return $this;
    }
}
