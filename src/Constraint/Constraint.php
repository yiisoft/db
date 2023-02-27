<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * The Constraint is used with base class to define the name and the column names of a constraint. The class is mainly
 * used by the database abstraction layer {@see \Yiisoft\Db\Schema\Schema} to create and drop constraints.
 */
class Constraint
{
    private string|array|null $columnNames = null;
    private string|null|object $name = null;

    /**
     * @return array|string|null The list of column names the constraint belongs to.
     */
    public function getColumnNames(): array|string|null
    {
        return $this->columnNames;
    }

    /**
     * @return object|string|null The constraint name.
     */
    public function getName(): object|string|null
    {
        return $this->name;
    }

    /**
     * Set the list of column names the constraint belongs to.
     *
     * @param array|string|null $value The list of column names the constraint belongs to.
     */
    public function columnNames(array|string|null $value): static
    {
        $this->columnNames = $value;

        return $this;
    }

    /**
     * Set the constraint name.
     *
     * @param object|string|null $value The constraint name.
     */
    public function name(object|string|null $value): static
    {
        $this->name = $value;

        return $this;
    }
}
