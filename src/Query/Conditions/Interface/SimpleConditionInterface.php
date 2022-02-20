<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Iterator;
use Yiisoft\Db\Expression\ExpressionInterface;

interface SimpleConditionInterface extends ConditionInterface, ExpressionInterface
{
    /**
     * @psalm-return string|string[]|ExpressionInterface The column name. If it is an array, a composite `IN` condition
     * will be generated.
     */
    public function getColumn(): string|array|ExpressionInterface;

    /**
     * @return string The operator to use. Anything could be used e.g. `>`, `<=`, etc.
     */
    public function getOperator(): string;

    /**
     * @return array|int|string|Iterator|ExpressionInterface|null The value to the right of the {@see operator}.
     */
    public function getValue(): array|int|string|Iterator|ExpressionInterface|null;
}
