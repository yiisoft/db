<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Statement;

use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Represents the condition and the result of a `WHEN` clause in a SQL `CASE` statement.
 *
 * @see CaseX
 */
final class When
{
    /**
     * @param mixed $condition The condition for the `WHEN` clause:
     * - `string` is treated as a SQL expression;
     * - `array` is treated as a condition to check, see {@see QueryInterface::where()};
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     * @param mixed $result The result to return if the condition is `true`:
     * - `string` is treated as a SQL expression;
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     */
    public function __construct(
        public readonly mixed $condition,
        public readonly mixed $result,
    ) {
    }
}
