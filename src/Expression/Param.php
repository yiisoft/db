<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents a parameter used in building an SQL statement.
 *
 * It can be used to represent a placeholder in an SQL statement, and can be bound to a specific value when the
 * statement is executed.
 *
 * It can also represent a column name or table name, depending on the context in which it's
 * used. The class provides methods for specifying the parameter name, value, as well as methods for quoting and
 * escaping the parameter value to ensure that it's handled by the database.
 */
final class Param implements ExpressionInterface
{
    /**
     * @param mixed $value The value to bind to the parameter.
     * @param int $type The SQL data type of the parameter.
     * If `null`, the type is determined by the PHP type of the value.
     *
     * @psalm-param DataType::* $type
     */
    public function __construct(
        public readonly mixed $value,
        public readonly int $type,
    ) {
    }
}
