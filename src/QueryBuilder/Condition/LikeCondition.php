<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Iterator;
use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

/**
 * Condition that represents `LIKE` operator.
 */
final class LikeCondition implements ConditionInterface
{
    protected array|null $escapingReplacements = [];

    public function __construct(
        private readonly string|ExpressionInterface $column,
        private readonly string $operator,
        private readonly array|int|string|Iterator|ExpressionInterface|null $value,
        private readonly ?bool $caseSensitive = null,
    ) {
    }

    /**
     * @return ExpressionInterface|string The column name.
     */
    public function getColumn(): string|ExpressionInterface
    {
        return $this->column;
    }

    /**
     * @see setEscapingReplacements()
     */
    public function getEscapingReplacements(): ?array
    {
        return $this->escapingReplacements;
    }

    /**
     * @return string The operator to use such as `>` or `<=`.
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return array|ExpressionInterface|int|Iterator|string|null The value to the right of {@see operator}.
     */
    public function getValue(): array|int|string|Iterator|ExpressionInterface|null
    {
        return $this->value;
    }

    /**
     * @return bool|null Whether the comparison is case-sensitive. `null` means using the default behavior.
     */
    public function getCaseSensitive(): ?bool
    {
        return $this->caseSensitive;
    }

    /**
     * This method allows specifying how to escape special characters in the value(s).
     *
     * @param array|null $escapingReplacements An array of mappings from the special characters to their escaped
     * counterparts.
     *
     * You may use an empty array to indicate the values are already escaped and no escape should be applied.
     * Note that when using an escape mapping (or the third operand isn't provided), the values will be automatically
     * inside within a pair of percentage characters.
     */
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
            isset($operands['caseSensitive']) ? (bool) $operands['caseSensitive'] : null,
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
