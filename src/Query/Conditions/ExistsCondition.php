<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Query\Conditions\Interface\ExistConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Condition that represents `EXISTS` operator.
 */
class ExistsCondition implements ExistConditionInterface
{
    public function __construct(private string $operator, private QueryInterface $query)
    {
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0]) || !$operands[0] instanceof QueryInterface) {
            throw new InvalidArgumentException('Subquery for EXISTS operator must be a Query object.');
        }

        return new static($operator, $operands[0]);
    }
}
