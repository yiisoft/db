<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Iterator;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

interface InConditionInterface extends ConditionInterface
{
    /**
     * @return array|Iterator|string The column name. If it is an array, a composite `IN` condition will be
     * generated.
     */
    public function getColumn(): array|string|Iterator|ExpressionInterface;

    /**
     * @return string The operator to use (e.g. `IN` or `NOT IN`).
     */
    public function getOperator(): string;

    /**
     * @return int|iterable|Iterator|QueryInterface An array of values that {@see columns} value should be among.
     *
     * If it is an empty array the generated expression will be a `false` value if {@see operator} is `IN` and empty if
     * operator is `NOT IN`.
     */
    public function getValues(): int|iterable|Iterator|QueryInterface;
}
