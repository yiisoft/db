<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * ForeignKeyConstraint represents the metadata of a table `FOREIGN KEY` constraint.
 */
class ForeignKeyConstraint extends Constraint
{
    private ?string $foreignSchemaName = null;
    private $foreignTableName;
    private $foreignColumnNames;
    private ?string $onUpdate = null;
    private ?string $onDelete = null;

    public function getForeignSchemaName(): ?string
    {
        return $this->foreignSchemaName;
    }

    public function getForeignTableName()
    {
        return $this->foreignTableName;
    }

    public function getForeignColumnNames(): array
    {
        return $this->foreignColumnNames;
    }

    public function getOnUpdate(): ?string
    {
        return $this->onUpdate;
    }

    public function getOnDelete($value): ?string
    {
        return $this->onDelete;
    }

    /**
     * @param string|null $value referenced table schema name.
     *
     * @return self
     */
    public function foreignSchemaName(?string $value): self
    {
        $this->foreignSchemaName = $value;

        return $this;
    }

    /**
     * @param object|string|null $value referenced table name.
     *
     * @return self
     */
    public function foreignTableName($value): self
    {
        $this->foreignTableName = $value;

        return $this;
    }

    /**
     * @param array|string $value list of referenced table column names.
     *
     * @return self
     */
    public function foreignColumnNames($value): self
    {
        $this->foreignColumnNames = $value;

        return $this;
    }

    /**
     * @param string|null $value referential action if rows in a referenced table are to be updated.
     *
     * @return self
     */
    public function onUpdate(?string $value): self
    {
        $this->onUpdate = $value;

        return $this;
    }

    /**
     * @param string|null $value referential action if rows in a referenced table are to be deleted.
     *
     * @return self
     */
    public function onDelete(?string $value): self
    {
        $this->onDelete = $value;

        return $this;
    }
}
