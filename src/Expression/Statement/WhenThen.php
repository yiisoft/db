<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Statement;

use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Represents the condition and the result of a `WHEN-THEN` clause in a SQL `CASE` statement.
 *
 * @see CaseX
 */
final class WhenThen
{
    /**
     * @param mixed $when The value or condition for the `WHEN-THEN` clause:
     * - `array` is treated as a condition to check, see {@see QueryInterface::where()};
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     * @param mixed $then The result to return if the condition is `true`. The value will be converted to its string
     * representation using {@see QueryBuilderInterface::buildValue()}.
     */
    public function __construct(
        public readonly mixed $when,
        public readonly mixed $then,
    ) {}
}
