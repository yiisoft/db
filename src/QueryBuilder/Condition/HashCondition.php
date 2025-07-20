<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Yiisoft\Db\QueryBuilder\Condition\ConditionInterface;

/**
 * Condition based on column-value pairs.
 */
final class HashCondition implements ConditionInterface
{
    public function __construct(private array|null $hash = [])
    {
    }

    /**
     * @return array|null The condition specification.
     */
    public function getHash(): array|null
    {
        return $this->hash;
    }

    /**
     * Creates a condition based on the given operator and operands.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new self($operands);
    }
}
