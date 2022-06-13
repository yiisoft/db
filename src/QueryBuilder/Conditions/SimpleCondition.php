<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\SimpleConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

use function count;

/**
 * Class SimpleCondition represents a simple condition like `"column" operator value`.
 */
final class SimpleCondition implements SimpleConditionInterface
{
    public function __construct(
        private string|Expression|QueryInterface $column,
        private string $operator,
        private array|int|string|Iterator|ExpressionInterface|null $value
    ) {
    }

    public function getColumn(): string|Expression|QueryInterface
    {
        return $this->column;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): array|int|string|Iterator|ExpressionInterface|null
    {
        return $this->value;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @psalm-suppress MixedArgument
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (count($operands) !== 2) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new self($operands[0], $operator, $operands[1]);
    }
}
