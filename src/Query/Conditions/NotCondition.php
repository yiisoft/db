<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Query\Conditions\Builder\NotConditionBuilder;
use Yiisoft\Db\Query\Conditions\Interface\NotConditionInterface;
use Yiisoft\Db\Query\QueryBuilderInterface;

use function array_shift;
use function count;

/**
 * Condition that inverts passed {@see condition}.
 */
class NotCondition implements NotConditionInterface
{
    public function __construct(private mixed $condition)
    {
    }

    public function getCondition(): mixed
    {
        return $this->condition;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (count($operands) !== 1) {
            throw new InvalidArgumentException("Operator '$operator' requires exactly one operand.");
        }

        return new static(array_shift($operands));
    }
}
