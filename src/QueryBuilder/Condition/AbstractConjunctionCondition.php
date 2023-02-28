<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Yiisoft\Db\QueryBuilder\Condition\Interface\ConjunctionConditionInterface;

/**
 * Class AbstractConjunctionCondition represents a conjunction condition.
 */
abstract class AbstractConjunctionCondition implements ConjunctionConditionInterface
{
    final public function __construct(protected array $expressions)
    {
    }

    public function getExpressions(): array
    {
        return $this->expressions;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new static($operands);
    }
}
