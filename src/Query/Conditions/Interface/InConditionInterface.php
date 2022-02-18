<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Iterator;
use Traversable;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

interface InConditionInterface extends ConditionInterface, ExpressionInterface
{
    /**
     * @psalm-return string|string[]|Iterator The column name. If it is an array, a composite `IN` condition will be
     * generated.
     */
    public function getColumn(): array|string|Iterator;

    /**
     * @return string The operator to use (e.g. `IN` or `NOT IN`).
     */
    public function getOperator(): string;

    /**
     * @return array|int|Iterator|QueryInterface An array of values that {@see columns} value should be among.
     *
     * If it is an empty array the generated expression will be a `false` value if {@see operator} is `IN` and empty if
     * operator is `NOT IN`.
     */
    public function getValues(): array|int|Iterator|QueryInterface;
}
