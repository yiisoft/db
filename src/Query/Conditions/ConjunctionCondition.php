<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Query\Conditions\Builder\ConjunctionConditionBuilder;
use Yiisoft\Db\Query\Conditions\Interface\ConjunctionConditionInterface;
use Yiisoft\Db\Query\QueryBuilderInterface;

/**
 * Class ConjunctionCondition.
 */
abstract class ConjunctionCondition implements ConjunctionConditionInterface
{
    public function __construct(protected array $expressions)
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
