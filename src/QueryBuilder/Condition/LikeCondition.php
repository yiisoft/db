<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\LikeConditionInterface;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

/**
 * Condition that represents `LIKE` operator.
 */
final class LikeCondition implements LikeConditionInterface
{
    protected array|null $escapingReplacements = [];

    public function __construct(
        private string|ExpressionInterface $column,
        private string $operator,
        private array|int|string|Iterator|ExpressionInterface|null $value
    ) {
    }

    public function getColumn(): string|ExpressionInterface
    {
        return $this->column;
    }

    public function getEscapingReplacements(): ?array
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

    public function setEscapingReplacements(array|null $escapingReplacements): void
    {
        $this->escapingReplacements = $escapingReplacements;
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 2.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        $condition = new self(
            self::validateColumn($operator, $operands[0]),
            $operator,
            self::validateValue($operator, $operands[1]),
        );

        if (array_key_exists(2, $operands) && (is_array($operands[2]) || $operands[2] === null)) {
            $condition->setEscapingReplacements($operands[2]);
        }

        return $condition;
    }

    /**
     * Validates the given column to be `string` or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException
     */
    private static function validateColumn(string $operator, mixed $column): string|ExpressionInterface
    {
        if (is_string($column) || $column instanceof ExpressionInterface) {
            return $column;
        }

        throw new InvalidArgumentException("Operator '$operator' requires column to be string or ExpressionInterface.");
    }

    /**
     * Validates the given values to be `string`, `array`, `Iterator` or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the values aren't `string`, `array`, `Iterator` or `ExpressionInterface`.
     */
    private static function validateValue(
        string $operator,
        mixed $value
    ): array|int|string|Iterator|ExpressionInterface|null {
        if (
            is_string($value) ||
            is_array($value) ||
            is_int($value) ||
            $value instanceof Iterator ||
            $value instanceof ExpressionInterface ||
            $value === null
        ) {
            return $value;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires value to be string, array, Iterator or ExpressionInterface."
        );
    }
}
