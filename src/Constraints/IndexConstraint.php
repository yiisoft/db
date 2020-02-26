<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraints;

/**
 * IndexConstraint represents the metadata of a table `INDEX` constraint.
 */
class IndexConstraint extends Constraint
{
    /**
     * @var bool whether the index is unique.
     */
    private bool $isUnique = false;

    /**
     * @var bool whether the index was created for a primary key.
     */
    private bool $isPrimary = false;

    public function getIsUnique(): bool
    {
        return $this->isUnique;
    }

    public function getIsPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsUnique(bool $value): void
    {
        $this->isUnique = $value;
    }

    public function setIsPrimary(bool $value): void
    {
        $this->isPrimary = $value;
    }
}
