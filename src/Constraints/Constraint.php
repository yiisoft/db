<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraints;

/**
 * Constraint represents the metadata of a table constraint.
 */
class Constraint
{
    /**
     * @var array|string|null list of column names the constraint belongs to.
     */
    private $columnNames;

    /**
     * @var object|string|null the constraint name.
     */
    public $name;

    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setColumnNames($value): void
    {
        $this->columnNames = $value;
    }

    public function setName($value): void
    {
        $this->name = $value;
    }
}
