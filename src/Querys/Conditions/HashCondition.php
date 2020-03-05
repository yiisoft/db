<?php

declare(strict_types=1);

namespace Yiisoft\Db\Querys\Conditions;

/**
 * Condition based on column-value pairs.
 */
class HashCondition implements ConditionInterface
{
    private ?array $hash = [];

    public function __construct(?array $hash = [])
    {
        $this->hash = $hash;
    }

    /**
     * @return array|null the condition specification.
     */
    public function getHash(): ?array
    {
        return $this->hash;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new static($operands);
    }
}
