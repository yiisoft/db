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
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new self(self::validateCondition($operator, $operands));
    }

    private static function validateCondition(string $operator, array $operands): ExpressionInterface|array|null|string
    {
        if (count($operands) !== 1) {
            throw new InvalidArgumentException("Operator '$operator' requires exactly one operand.");
        }

        /** @var mixed $operands */
        $operands = array_shift($operands);

        if (
            !is_array($operands) &&
            !($operands instanceof ExpressionInterface) &&
            !is_string($operands) &&
            $operands !== null
        ) {
            throw new InvalidArgumentException(
                "Operator '$operator' requires condition to be array, string, null or ExpressionInterface."
            );
        }

        return $operands;
    }
}
