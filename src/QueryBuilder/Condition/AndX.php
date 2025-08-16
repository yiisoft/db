<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Condition that connects two or more SQL expressions with the `AND` operator.
 */
final class AndX implements ConditionInterface
{
    public readonly array $expressions;

    /**
     * @param array $expressions The expressions that are connected by this condition.
     *
     * @psalm-param array<array|ExpressionInterface|scalar> $expressions
     */
    public function __construct(
        array|ExpressionInterface|int|float|bool|string ...$expressions,
    ) {
        $this->expressions = $expressions;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        /** @psalm-var array<array|ExpressionInterface|scalar> $operands */
        return new self(...$operands);
    }
}
