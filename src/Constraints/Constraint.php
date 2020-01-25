<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraints;

/**
 * Constraint represents the metadata of a table constraint.
 */
class Constraint
{
    /**
     * @var array list of column names the constraint belongs to.
     */
    private array $columnNames = [];

    /**
     * @var string|null the constraint name.
     */
    private ?string $name = null;

    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setColumnNames(array $value): void
    {
        $this->columnNames = $value;
    }

    public function setName(?string $value): void
    {
        $this->name = $value;
    }
}
