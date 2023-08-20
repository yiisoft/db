<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

use Stringable;

/**
 * Represents a constraint in a database.
 *
 * A constraint is a rule that's enforced on the data in a table, such as a primary key, a foreign key, a default value,
 * check or index constraint.
 *
 * It's the base class for all constraint classes, and defines methods that are common and shared by all constrained
 * classes, with name and columnNames being the most common ones.
 */
class Constraint
{
    private string|array|null $columnNames = null;
    private Stringable|string|null $name = null;

    /**
     * @return array|string|null The list of column names the constraint belongs to.
     */
    public function getColumnNames(): array|string|null
    {
        return $this->columnNames;
    }

    /**
     * @return \Stringable|string|null The constraint name.
     */
    public function getName(): Stringable|string|null
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
     * @param \Stringable|string|null $value The constraint name.
     */
    public function name(Stringable|string|null $value): static
    {
        $this->name = $value;
        return $this;
    }
}
