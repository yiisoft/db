<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents a value for SQL queries.
 *
 * This class encapsulates any type of value that needs to be properly converted and bound as a parameter in SQL
 * statements.
 */
final class Value implements ExpressionInterface
{
    /**
     * @param mixed $value The value to be used in the SQL query.
     */
    public function __construct(
        public readonly mixed $value,
    ) {}
}
