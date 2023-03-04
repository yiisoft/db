<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * The IndexConstraint represents a constraint on a database index, such as a primary key or unique constraint.
 * The class allows working with index constraints: checking if a constraint is primary key or unique, getting the name
 * of the constraint, and getting the columns that are part of the constraint. It is typically used by the database
 * schema management tools in Yii to manage and manipulate the indexes on a database table.
 */
final class IndexConstraint extends Constraint
{
    private bool $isUnique = false;
    private bool $isPrimary = false;

    /**
     * @return bool whether the index is unique.
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * @return bool whether the index was created for a primary key.
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * Set whether the index is unique.
     *
     * @param bool $value whether the index is unique.
     */
    public function unique(bool $value): self
    {
        $this->isUnique = $value;
        return $this;
    }

    /**
     * Set whether the index was created for a primary key.
     *
     * @param bool $value whether the index was created for a primary key.
     */
    public function primary(bool $value): self
    {
        $this->isPrimary = $value;
        return $this;
    }
}
