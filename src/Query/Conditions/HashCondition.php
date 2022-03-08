<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Query\Conditions\Interface\HashConditionInterface;

/**
 * Condition based on column-value pairs.
 */
final class HashCondition implements HashConditionInterface
{
    public function __construct(private ?array $hash = [])
    {
    }

    public function getHash(): ?array
    {
        return $this->hash;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new static($operands);
    }
}
