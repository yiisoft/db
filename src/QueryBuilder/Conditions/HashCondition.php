<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

use Yiisoft\Db\QueryBuilder\Conditions\Interface\HashConditionInterface;

/**
 * Condition based on column-value pairs.
 */
final class HashCondition implements HashConditionInterface
{
    public function __construct(private array|null $hash = [])
    {
    }

    public function getHash(): array|null
    {
        return $this->hash;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new self($operands);
    }
}
