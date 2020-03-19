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
     */
    public function setIsUnique(bool $value): void
    {
        $this->isUnique = $value;
    }

    /**
     * @var bool $value whether the index was created for a primary key.
     */
    public function setIsPrimary(bool $value): void
    {
        $this->isPrimary = $value;
    }
}
