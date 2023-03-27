<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Iterator;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Represents a condition that's based on `IN` operator.
 */
interface InConditionInterface extends ConditionInterface
{
    /**
     * @return array|ExpressionInterface|Iterator|string The column name. If it's an array, a composite `IN` condition
     * will be generated.
     */
    public function getColumn(): array|string|ExpressionInterface|Iterator;

    /**
     * @return string The operator to use (for example, `IN` or `NOT IN`).
     */
    public function getOperator(): string;

    /**
     * @return int|iterable|Iterator|QueryInterface An array of values that {@see columns} value should be among.
     *
     * If it's an empty array, the generated expression will be a `false` value if {@see operator} is `IN` and empty if
     * operator is `NOT IN`.
     */
    public function getValues(): int|iterable|Iterator|QueryInterface;
}
