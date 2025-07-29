<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Condition based on column-value pairs.
 */
final class Columns implements ConditionInterface
{
    /**
     * @param array $values The condition specification.
     *
     * @psalm-param array<string, mixed> $values
     */
    public function __construct(
        public readonly array $values = [],
    ) {
    }

    /**
     * Creates a condition based on the given operator and operands.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        /** @psalm-var array<string, mixed> $operands */
        return new self($operands);
    }
}
