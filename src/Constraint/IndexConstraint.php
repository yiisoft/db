<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * IndexConstraint represents the metadata of a table `INDEX` constraint.
 */
class IndexConstraint extends Constraint
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
     *
     * @return $this
     */
    public function unique(bool $value): self
    {
        $this->isUnique = $value;

        return $this;
    }

    /**
     * @param bool $value whether the index was created for a primary key.
     *
     * @return $this
     */
    public function primary(bool $value): self
    {
        $this->isPrimary = $value;

        return $this;
    }
}
