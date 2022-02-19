<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

/**
 * Condition based on column-value pairs.
 */
class HashCondition implements ConditionInterface
{
    public function __construct(private ?array $hash = [])
    {
    }

    /**
     * @return array|null the condition specification.
     */
    public function getHash(): ?array
    {
        return $this->hash;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new static($operands);
    }
}
