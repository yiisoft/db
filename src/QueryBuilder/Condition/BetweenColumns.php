<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Represents a `BETWEEN` operator where values are between two columns.
 *
 * For example:
 *
 * ```php
 * new BetweenColumnsCondition(42, 'min_value', 'max_value')
 * // Will be build to:
 * // 42 BETWEEN min_value AND max_value
 * ```
 *
 * And a more complex example:
 *
 * ```php
 * new BetweenColumnsCondition(
 *    new Expression('NOW()'),
 *    (new Query)->select('time')->from('log')->orderBy('id ASC')->limit(1),
 *    'update_time'
 * );
 *
 * // Will be built to:
 * // NOW() BETWEEN (SELECT time FROM log ORDER BY id ASC LIMIT 1) AND update_time
 * ```
 */
final class BetweenColumns extends AbstractBetweenColumns
{
}
