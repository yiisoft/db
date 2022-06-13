<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Interface;

use Yiisoft\Db\Expression\Expression;

interface BetweenConditionInterface extends ConditionInterface
{
    /**
     * @psalm-return string|Expression The column name.
     */
    public function getColumn(): string|Expression;

    /**
     * @return mixed End of the interval.
     */
    public function getIntervalEnd(): mixed;

    /**
     * @return mixed Beginning of the interval.
     */
    public function getIntervalStart(): mixed;

    /**
     * @return string The operator to use (e.g. `BETWEEN` or `NOT BETWEEN`).
     */
    public function getOperator(): string;
}
