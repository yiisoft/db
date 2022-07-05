<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\BetweenColumnsConditionInterface;

/**
 * Class BetweenColumnCondition represents a `BETWEEN` condition where values are between two columns.
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
     * @throws InvalidArgumentException
     *
     * @psalm-suppress MixedArgument
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidArgumentException("Operator '$operator' requires three operands.");
        }

        return new self($operands[0], $operator, $operands[1], $operands[2]);
    }
}
