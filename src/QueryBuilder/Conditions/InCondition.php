<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\InConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Class InCondition represents `IN` condition.
 */
final class InCondition implements InConditionInterface
{
    public function __construct(
        private array|string|Iterator $column,
        private string $operator,
        private int|iterable|Iterator|QueryInterface $values
    ) {
    }

    public function getColumn(): array|string|Iterator
    {
        return $this->column;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValues(): int|iterable|Iterator|QueryInterface
    {
        return $this->values;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new self(
            self::validateColumn($operator, $operands[0]),
            $operator,
            self::validateValues($operator, $operands[1]),
        );
    }

    private static function validateColumn(string $operator, mixed $operand): array|string|Iterator
    {
        if (!is_string($operand) && !is_array($operand) && !$operand instanceof Iterator) {
            throw new InvalidArgumentException("Operator '$operator' requires column to be string, array or Iterator.");
        }

        return $operand;
    }

    private static function validateValues(string $operator, mixed $operand): int|iterable|Iterator|QueryInterface
    {
        if (!is_array($operand) && !$operand instanceof Iterator && !is_int($operand) && !$operand instanceof QueryInterface) {
            throw new InvalidArgumentException(
                "Operator '$operator' requires values to be array, Iterator, int or QueryInterface."
            );
        }

        return $operand;
    }
}
