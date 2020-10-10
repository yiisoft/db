<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Constraint represents the metadata of a table constraint.
 */
class Constraint
{
    /** @var array|string|null */
    private $columnNames;
    /** @var object|string|null */
    private $name;

    public function getColumnNames()
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
     * @return $this
     */
    public function columnNames($value): self
    {
        $this->columnNames = $value;

        return $this;
    }

    /**
     * @param object|string|null $value the constraint name.
     *
     * @return $this
     */
    public function name($value): self
    {
        $this->name = $value;

        return $this;
    }
}
