<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\Interface\LikeConditionInterface;

/**
 * Class LikeCondition represents a `LIKE` condition.
 */
class LikeCondition implements LikeConditionInterface
{
    protected array|bool|null $escapingReplacements = null;

    public function __construct(
        private string|array|ExpressionInterface $column,
        private string $operator,
        private array|int|string|Iterator|ExpressionInterface|null $value
    ) {
    }

    public function getColumn(): string|array|ExpressionInterface
    {
        return $this->column;
    }

    public function getEscapingReplacements(): array|bool|null
    {
        return $this->escapingReplacements;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): array|int|string|Iterator|ExpressionInterface|null
    {
        return $this->value;
    }

    public function setEscapingReplacements(array|bool|null $escapingReplacements): void
    {
        $this->escapingReplacements = $escapingReplacements;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        $condition = new static($operands[0], $operator, $operands[1]);

        if (isset($operands[2])) {
            $condition->escapingReplacements = $operands[2];
        }

        return $condition;
    }
}
