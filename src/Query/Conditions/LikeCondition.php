<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;

/**
 * Class LikeCondition represents a `LIKE` condition.
 */
class LikeCondition extends SimpleCondition
{
    protected $escapingReplacements = null;

    /**
     * @return array|bool|null
     */
    public function getEscapingReplacements()
    {
        return $this->escapingReplacements;
    }

    /**
     * This method allows to specify how to escape special characters in the value(s).
     *
     * @param array|bool|null an array of mappings from the special characters to their escaped counterparts.
     * You may use `false` or an empty array to indicate the values are already escaped and no escape should be applied.
     * Note that when using an escape mapping (or the third operand is not provided), the values will be automatically
     * enclosed within a pair of percentage characters.
     */
    public function setEscapingReplacements($escapingReplacements): void
    {
        $this->escapingReplacements = $escapingReplacements;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException if wrong number of operands have been given.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        $condition = new static($operands[0], $operator, $operands[1]);

        if (isset($operands[2])) {
            $condition->escapingReplacements = $operands[2];
        }

        return $condition;
    }
}
