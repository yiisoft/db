<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Class InCondition represents `IN` condition.
 */
class InCondition implements ConditionInterface
{
    /**
     * @psalm-param QueryInterface|iterable<mixed, mixed>|int|Iterator $values
     */
    public function __construct(
        private mixed $column,
        private string $operator,
        private array|int|Iterator|QueryInterface $values
    ) {
    }

    /**
     * @return string the operator to use (e.g. `IN` or `NOT IN`).
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return string|string[] the column name. If it is an array, a composite `IN` condition will be generated.
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return array|int|Iterator (ExpressionInterface|string)[]|int an array of values that {@see columns} value should
     * be among.
     *
     * If it is an empty array the generated expression will be a `false` value if {@see operator} is `IN` and empty if
     * operator is `NOT IN`.
     *
     * @psalm-return QueryInterface|iterable<mixed, mixed>|int|object
     */
    public function getValues(): array|int|Iterator|QueryInterface
    {
        return $this->values;
    }

    /**
     * @throws InvalidArgumentException if wrong number of operands have been given.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new static($operands[0], $operator, $operands[1]);
    }
}
