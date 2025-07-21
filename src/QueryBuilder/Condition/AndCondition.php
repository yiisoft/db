<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Condition that connects two or more SQL expressions with the `AND` operator.
 */
final class AndCondition implements ConditionInterface
{
    /**
     * @param array $expressions The expressions that are connected by this condition.
     *
     * @psalm-param array<array|ExpressionInterface|scalar> $expressions
     */
    public function __construct(
        public readonly array $expressions,
    ) {
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        /** @psalm-var array<array|ExpressionInterface|scalar> $operands */
        return new self($operands);
    }
}
