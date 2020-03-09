<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Constraint represents the metadata of a table constraint.
 */
class Constraint
{
    private $columnNames;
    private $name;

    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array|string|null $value list of column names the constraint belongs to.
     *
     * @return void
     */
    public function setColumnNames($value): void
    {
        $this->columnNames = $value;
    }

    /**
     * @param object|string|null $value the constraint name.
     *
     * @return void
     */
    public function setName($value): void
    {
        $this->name = $value;
    }
}
