<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * The DefaultValueConstraint serves for setting default values of columns in a database table. It can be used to
 * specify a default value for a column when a new row is inserted into the table and no value is provided for that
 * column. The class allows you to define a default value as a constant, a PHP expression, or a SQL expression that
 * will be evaluated by the database. This constraint can be used in migrations to define the default value for a
 * column when creating or modifying a table.
 */
final class DefaultValueConstraint extends Constraint
{
    private mixed $value = null;

    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed The default value as returned by the DBMS.
     */
    public function value(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }
}
