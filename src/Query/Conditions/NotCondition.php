<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;

use function array_shift;
use function count;

/**
 * Condition that inverts passed {@see condition}.
 */
class NotCondition implements ConditionInterface
{
    private $condition;

    public function __construct($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return mixed the condition to be negated.
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException if wrong number of operands have been given.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (count($operands) !== 1) {
            throw new InvalidArgumentException("Operator '$operator' requires exactly one operand.");
        }

        return new static(array_shift($operands));
    }
}
