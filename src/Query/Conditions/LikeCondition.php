<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Query\Conditions\Interface\LikeConditionInterface;

/**
 * Class LikeCondition represents a `LIKE` condition.
 */
class LikeCondition extends SimpleCondition implements LikeConditionInterface
{
    protected $escapingReplacements = null;

    public function getEscapingReplacements(): array|bool|null
    {
        return $this->escapingReplacements;
    }

    public function setEscapingReplacements(array|bool|null $escapingReplacements): void
    {
        $this->escapingReplacements = $escapingReplacements;
    }

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
