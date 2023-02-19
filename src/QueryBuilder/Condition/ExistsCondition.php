<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Condition\Interface\ExistConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Condition that represents `EXISTS` operator.
 */
final class ExistsCondition implements ExistConditionInterface
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

    /**
     * @throws InvalidArgumentException
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (isset($operands[0]) && $operands[0] instanceof QueryInterface) {
            return new self($operator, $operands[0]);
        }

        throw new InvalidArgumentException('Sub query for EXISTS operator must be a Query object.');
    }
}
