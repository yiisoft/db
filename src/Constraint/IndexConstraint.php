<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Represents an index constraint in a database.
 *
 * An index constraint is a constraint that enforces uniqueness or non-uniqueness of a column or a set of columns.
 *
 * It has information about the table and column(s) that the constraint applies to, as well as whether the index is
 * unique.
 */
final class IndexConstraint extends Constraint
{
    private bool $isUnique = false;
    private bool $isPrimary = false;

    /**
     * @return bool Whether the index is unique.
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * @return bool Whether the index was created for a primary key.
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * Set whether the index is unique.
     *
     * @param bool $value Whether the index is unique.
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
