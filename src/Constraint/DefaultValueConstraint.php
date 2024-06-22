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
final class DefaultValueConstraint extends Constraint
{
    private mixed $value = null;

    /**
     * @return mixed The default value as returned by the DBMS.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Set the default value as returned by the DBMS.
     *
     * @param mixed $value The default value as returned by the DBMS.
     */
    public function value(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }
}
