<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents a column name expression for SQL queries.
 *
 * This class encapsulates a column name that will be properly quoted when building SQL queries.
 */
final class ColumnName implements ExpressionInterface
{
    /**
     * @param string $name The column name.
     */
    public function __construct(
        public readonly string $name,
    ) {}
}
