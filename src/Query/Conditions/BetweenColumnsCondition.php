<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Class BetweenColumnCondition represents a `BETWEEN` condition where values is between two columns.
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
class BetweenColumnsCondition implements ConditionInterface
{
    public function __construct(
        private mixed $value,
        private string $operator,
        private mixed $intervalStartColumn,
        private mixed $intervalEndColumn
    ) {
    }

    /**
     * @return string the operator to use (e.g. `BETWEEN` or `NOT BETWEEN`).
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return mixed the value to compare against.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return ExpressionInterface|QueryInterface|string the column name or expression that is a beginning of the
     * interval.
     */
    public function getIntervalStartColumn(): ExpressionInterface|QueryInterface|string
    {
        return $this->intervalStartColumn;
    }

    /**
     * @return ExpressionInterface|QueryInterface|string the column name or expression that is an end of the interval.
     */
    public function getIntervalEndColumn(): ExpressionInterface|QueryInterface|string
    {
        return $this->intervalEndColumn;
    }

    /**
     * @throws InvalidArgumentException if wrong number of operands have been given.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidArgumentException("Operator '$operator' requires three operands.");
        }

        return new static($operands[0], $operator, $operands[1], $operands[2]);
    }
}
