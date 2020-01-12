<?php

namespace Yiisoft\Db;

/**
 * Constraint represents the metadata of a table constraint.
 */
class Constraint
{
    /**
     * @var string[]|null list of column names the constraint belongs to.
     */
    public $columnNames = [];

    /**
     * @var string|null the constraint name.
     */
    public $name;

    public function setColumnNames($value): void
    {
        $this->columnNames = $value;
    }

    public function setName($value): void
    {
        $this->name = $value;
    }
}
