<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\LikeConditionInterface;

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

        if (array_key_exists(2, $operands) && (is_array($operands[2]) || $operands[2] === null)) {
            $condition->setEscapingReplacements($operands[2]);
        }

        return $condition;
    }

    private static function validateColumn(string $operator, mixed $column): string|Expression
    {
        if (!is_string($column) && !$column instanceof Expression) {
            throw new InvalidArgumentException("Operator '$operator' requires column to be string or Expression.");
        }

        return $column;
    }

    private static function validateValue(
        string $operator,
        mixed $value
    ): array|int|string|Iterator|ExpressionInterface|null {
        if (
            !is_string($value) &&
            !is_array($value) &&
            !is_int($value) &&
            !$value instanceof Iterator &&
            !$value instanceof ExpressionInterface &&
            $value !== null
        ) {
            throw new InvalidArgumentException(
                "Operator '$operator' requires value to be string, array, Iterator or ExpressionInterface."
            );
        }

        return $value;
    }
}
