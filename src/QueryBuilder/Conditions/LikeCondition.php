<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\LikeConditionInterface;

/**
 * Class LikeCondition represents a `LIKE` condition.
 */
final class LikeCondition implements LikeConditionInterface
{
    protected array|null $escapingReplacements = [];

    public function __construct(
        private string|Expression $column,
        private string $operator,
        private array|int|string|Iterator|ExpressionInterface|null $value
    ) {
    }

    public function getColumn(): string|Expression
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
     * @throws InvalidArgumentException
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

        if (array_key_exists(2, $operands) && is_array($operands[2])) {
            $condition->setEscapingReplacements($operands[2]);
        }

        return $condition;
    }

    private static function validateColumn(string $operator, mixed $operand): string|Expression
    {
        if (!is_string($operand) && !$operand instanceof Expression) {
            throw new InvalidArgumentException("Operator '$operator' requires column to be string or Expression.");
        }

        return $operand;
    }

    private static function validateValue(
        string $operator,
        mixed $operand
    ): array|int|string|Iterator|ExpressionInterface|null {
        if (
            !is_string($operand) &&
            !is_array($operand) &&
            !$operand instanceof Iterator &&
            !$operand instanceof ExpressionInterface &&
            $operand !== null
        ) {
            throw new InvalidArgumentException(
                "Operator '$operator' requires value to be string, array, Iterator or ExpressionInterface."
            );
        }

        return $operand;
    }
}
