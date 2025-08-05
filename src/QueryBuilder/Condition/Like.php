<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Iterator;
use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

use function is_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * Condition that represents `LIKE` operator.
 */
final class Like implements ConditionInterface
{
    private const DEFAULT_ESCAPE = true;
    private const DEFAULT_MODE = LikeMode::Contains;

    /**
     * @param ExpressionInterface|string $column The column name.
     * @param string $operator The operator to use such as `>` or `<=`.
     * @param array|ExpressionInterface|int|Iterator|string|null $value The value to the right of {@see operator}.
     * @param bool|null $caseSensitive Whether the comparison is case-sensitive. `null` means using the default
     * behavior.
     * @param bool $escape Whether to escape the value. Defaults to `true`. If `false`, the value will be used as is
     * without escaping.
     * @param LikeMode $mode The mode for the LIKE operation (contains, starts with, ends with or custom pattern).
     */
    public function __construct(
        public readonly string|ExpressionInterface $column,
        public readonly string $operator,
        public readonly array|int|string|Iterator|ExpressionInterface|null $value,
        public readonly ?bool $caseSensitive = null,
        public readonly bool $escape = self::DEFAULT_ESCAPE,
        public readonly LikeMode $mode = self::DEFAULT_MODE,
    ) {
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

        if (isset($operands['mode'])) {
            $mode = $operands['mode'];
            if (!$mode instanceof LikeMode) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Operator "%s" requires "mode" to be an instance of %s. Got %s.',
                        $operator,
                        LikeMode::class,
                        get_debug_type($mode),
                    ),
                );
            }
        } else {
            $mode = self::DEFAULT_MODE;
        }

        return new self(
            self::validateColumn($operator, $operands[0]),
            $operator,
            self::validateValue($operator, $operands[1]),
            isset($operands['caseSensitive']) ? (bool) $operands['caseSensitive'] : null,
            isset($operands['escape']) ? (bool) $operands['escape'] : self::DEFAULT_ESCAPE,
            $mode,
        );
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
