<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Condition based on column-value pairs.
 */
final class HashCondition implements ConditionInterface
{
    /**
     * @param array|null $hash The condition specification.
     */
    public function __construct(
        public readonly array|null $hash = [],
    ) {
    }

    /**
     * Creates a condition based on the given operator and operands.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new self($operands);
    }
}
