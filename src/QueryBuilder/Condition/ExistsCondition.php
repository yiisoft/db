<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Condition that represents `EXISTS` operator that checks whether a sub-query returns any rows
 */
final class ExistsCondition implements ConditionInterface
{
    /**
     * @param string $operator The operator to use (for example, `EXISTS` or `NOT EXISTS`).
     * @param QueryInterface $query The {@see QueryInterfacee} implementation representing the sub-query.
     */
    public function __construct(
        public readonly string $operator,
        public readonly QueryInterface $query,
    ) {
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 1, and the first operand isn't a query object.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (isset($operands[0]) && $operands[0] instanceof QueryInterface) {
            return new self($operator, $operands[0]);
        }

        throw new InvalidArgumentException('Sub query for EXISTS operator must be a Query object.');
    }
}
