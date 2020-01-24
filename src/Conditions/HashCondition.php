<?php

declare(strict_types=1);

namespace Yiisoft\Db\Conditions;

/**
 * Condition based on column-value pairs.
 */
class HashCondition implements ConditionInterface
{
    /**
     * @var array|null the condition specification.
     */
    private ?array $hash = [];

    /**
     * HashCondition constructor.
     *
     * @param array|null $hash
     */
    public function __construct(?array $hash = [])
    {
        $this->hash = $hash;
    }

    /**
     * @return array|null
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
