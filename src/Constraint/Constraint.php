<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Constraint represents the metadata of a table constraint.
 */
class Constraint
{
    /** @var array|string|null */
    private string|array|null $columnNames = null;
    /** @var object|string|null */
    private string|null|object $name = null;

    public function getColumnNames(): array|string|null
    {
        return $this->columnNames;
    }

    public function getName(): object|string|null
    {
        return $this->name;
    }

    /**
     * @param array|string|null $value list of column names the constraint belongs to.
     *
     * @return $this
     */
    public function columnNames(array|string|null $value): self
    {
        $this->columnNames = $value;
        return $this;
    }

    /**
     * @param object|string|null $value the constraint name.
     *
     * @return $this
     */
    public function name(object|string|null $value): self
    {
        $this->name = $value;
        return $this;
    }
}
