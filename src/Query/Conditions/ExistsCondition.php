<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Condition that represents `EXISTS` operator.
 */
class ExistsCondition implements ConditionInterface
{
    public function __construct(private string $operator, private QueryInterface $query)
    {
        $this->operator = $operator;
        $this->query = $query;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0]) || !$operands[0] instanceof QueryInterface) {
            throw new InvalidArgumentException('Subquery for EXISTS operator must be a Query object.');
        }

        return new static($operator, $operands[0]);
    }

    /**
     * @return string the operator to use (e.g. `EXISTS` or `NOT EXISTS`).
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return QueryInterface the {@see Query} object representing the sub-query.
     */
    public function getQuery(): QueryInterface
    {
        return $this->query;
    }
}
