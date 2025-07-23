<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Represents a default value constraint in a database.
 *
 * A default value constraint is a constraint that enforces a default value for a column.
 *
 * It can be used to specify a default value for a column when a new row is inserted into the table, and no value is
 * provided for that column.
 *
 * Also allows you to define a default value as a constant, a PHP expression, or an SQL expression that will be
 * evaluated by the database, and can be used in migrations to define the default value for a column when creating or
 * modifying a table.
 */
final class DefaultValue
{
    /**
     * @param string $name The constraint name.
     * @param string[] $columnNames The list of column names the constraint belongs to.
     * @param mixed $value The default value as returned by the DBMS.
     */
    public function __construct(
        public readonly string $name = '',
        public readonly array $columnNames = [],
        public readonly mixed $value = null,
    ) {
    }
}
