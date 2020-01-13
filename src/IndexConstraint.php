<?php

namespace Yiisoft\Db;

/**
 * IndexConstraint represents the metadata of a table `INDEX` constraint.
 */
class IndexConstraint extends Constraint
{
    /**
     * @var bool whether the index is unique.
     */
    public $isUnique;

    /**
     * @var bool whether the index was created for a primary key.
     */
    public $isPrimary;

    public function setIsUnique(bool $value): void
    {
        $this->isUnique = $value;
    }

    public function setIsPrimary(bool $value): void
    {
        $this->isPrimary = $value;
    }
}
