<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Yiisoft\Db\Query\QueryInterface;

use function sprintf;

/**
 * @internal
 *
 * Represents `EXISTS` and `NOT EXISTS` operators.
 */
abstract class AbstractExists implements ConditionInterface
{
    /**
     * @param QueryInterface $query The {@see QueryInterface} implementation representing the sub-query.
     */
    final public function __construct(
        public readonly QueryInterface $query,
    ) {
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 1, and the first operand isn't a query object.
     */
    final public static function fromArrayDefinition(string $operator, array $operands): static
    {
        if (isset($operands[0]) && $operands[0] instanceof QueryInterface) {
            return new static($operands[0]);
        }

        throw new InvalidArgumentException(
            sprintf(
                'Sub-query for %s operator must be a Query object.',
                $operator
            )
        );
    }
}
