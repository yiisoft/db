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
     * @return self
     */
    public function columnNames($value): self
    {
        $this->columnNames = $value;

        return $this;
    }

    /**
     * @param object|string|null $value the constraint name.
     *
     * @return void
     */
    public function name($value): self
    {
        $this->name = $value;

        return $this;
    }
}
