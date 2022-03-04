<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Query\Conditions\Interface\ConjunctionConditionInterface;

/**
 * Class ConjunctionCondition.
 */
abstract class ConjunctionCondition implements ConjunctionConditionInterface
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
