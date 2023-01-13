<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * The IndexConstraint is a class, it represents a constraint on a database index, such as a primary key or unique
 * constraint. The class includes properties and methods for working with index constraints, such as checking if a
 * constraint is primary key or unique, getting the name of the constraint, and getting the columns that are part of
 * the constraint. It is typically used by the database schema management tools in Yii to manage and manipulate the
 * indexes on a database table.
 */
final class IndexConstraint extends Constraint
{
    private bool $isUnique = false;
    private bool $isPrimary = false;

    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * @param bool $value whether the index is unique.
     */
    public function unique(bool $value): self
    {
        $this->isUnique = $value;

        return $this;
    }

    /**
     * @param bool $value whether the index was created for a primary key.
     */
    public function primary(bool $value): self
    {
        $this->isPrimary = $value;

        return $this;
    }
}
