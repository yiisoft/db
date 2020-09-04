<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Query\Query;

/**
 * Condition that represents `EXISTS` operator.
 */
class ExistsCondition implements ConditionInterface
{
    private string $operator;
    private Query $query;

    public function __construct(string $operator, Query $query)
    {
        $this->operator = $operator;
        $this->query = $query;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0]) || !$operands[0] instanceof Query) {
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
     * @return Query the {@see Query} object representing the sub-query.
     */
    public function getQuery(): Query
    {
        return $this->query;
    }
}
