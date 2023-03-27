<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\BetweenColumnsConditionInterface;

use function is_array;
use function is_int;
use function is_string;

/**
 * Represents a `BETWEEN` operator where values are between two columns.
 *
 * For example:.
 *
 * ```php
 * new BetweenColumnsCondition(42, 'BETWEEN', 'min_value', 'max_value')
 * // Will be build to:
 * // 42 BETWEEN min_value AND max_value
 * ```
 *
 * And a more complex example:
 *
 * ```php
 * new BetweenColumnsCondition(
 *    new Expression('NOW()'),
 *    'NOT BETWEEN',
 *    (new Query)->select('time')->from('log')->orderBy('id ASC')->limit(1),
 *    'update_time'
 * );
 *
 * // Will be built to:
 * // NOW() NOT BETWEEN (SELECT time FROM log ORDER BY id ASC LIMIT 1) AND update_time
 * ```
 */
final class BetweenColumnsCondition implements BetweenColumnsConditionInterface
{
    public function __construct(
        private array|int|string|Iterator|ExpressionInterface $value,
        private string $operator,
        private string|ExpressionInterface $intervalStartColumn,
        private string|ExpressionInterface $intervalEndColumn
    ) {
    }

    public function getIntervalEndColumn(): string|ExpressionInterface
    {
        return $this->intervalEndColumn;
    }

    public function getIntervalStartColumn(): string|ExpressionInterface
    {
        return $this->intervalStartColumn;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): array|int|string|Iterator|ExpressionInterface
    {
        return $this->value;
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 3.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidArgumentException("Operator '$operator' requires three operands.");
        }

        return new self(
            self::validateValue($operator, $operands[0]),
            $operator,
            self::validateIntervalStartColumn($operator, $operands[1]),
            self::validateIntervalEndColumn($operator, $operands[2]),
        );
    }

    /**
     * Validates the given value to be `array`, `int`, `string`, `Iterator` or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the value isn't `array`, `int`, `string`, `Iterator` or `ExpressionInterface`.
     */
    private static function validateValue(
        string $operator,
        mixed $value
    ): array|int|string|Iterator|ExpressionInterface {
        if (
            is_array($value) ||
            is_int($value) ||
            is_string($value) ||
            ($value instanceof Iterator) ||
            ($value instanceof ExpressionInterface)
        ) {
            return $value;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires value to be array, int, string, Iterator or ExpressionInterface."
        );
    }

    /**
     * Validates the given interval start column to be string or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the interval start column isn't string or `ExpressionInterface`.
     */
    private static function validateIntervalStartColumn(
        string $operator,
        mixed $intervalStartColumn
    ): string|ExpressionInterface {
        if (
            is_string($intervalStartColumn) ||
            $intervalStartColumn instanceof ExpressionInterface
        ) {
            return $intervalStartColumn;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires interval start column to be string or ExpressionInterface."
        );
    }

    /**
     * Validates the given interval end column to be string or ExpressionInterface.
     *
     * @throws InvalidArgumentException If the interval end column isn't a string or ExpressionInterface.
     */
    private static function validateIntervalEndColumn(
        string $operator,
        mixed $intervalEndColumn
    ): string|ExpressionInterface {
        if (
            is_string($intervalEndColumn) ||
            $intervalEndColumn instanceof ExpressionInterface
        ) {
            return $intervalEndColumn;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires interval end column to be string or ExpressionInterface."
        );
    }
}
