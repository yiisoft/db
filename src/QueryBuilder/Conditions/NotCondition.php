<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\NotConditionInterface;

use function array_shift;
use function count;

/**
 * Condition that inverts passed {@see condition}.
 */
final class NotCondition implements NotConditionInterface
{
    public function __construct(private ExpressionInterface|array|null|string $condition)
    {
    }

    public function getCondition(): ExpressionInterface|array|null|string
    {
        return $this->condition;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @psalm-suppress MixedArgument
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (count($operands) !== 1) {
            throw new InvalidArgumentException("Operator '$operator' requires exactly one operand.");
        }

        return new self(array_shift($operands));
    }
}
