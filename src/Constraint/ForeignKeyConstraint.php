<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * ForeignKeyConstraint represents the metadata of a table `FOREIGN KEY` constraint.
 */
class ForeignKeyConstraint extends Constraint
{
    private ?string $foreignSchemaName = null;
    private ?string $foreignTableName;
    private array $foreignColumnNames;
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

    public function getOnDelete(): ?string
    {
        return $this->onDelete;
    }

    /**
     * @param string|null $value referenced table schema name.
     *
     * @return $this
     */
    public function foreignSchemaName(?string $value): self
    {
        $this->foreignSchemaName = $value;

        return $this;
    }

    /**
     * @param string|null $value referenced table name.
     *
     * @return $this
     */
    public function foreignTableName(?string $value): self
    {
        $this->foreignTableName = $value;

        return $this;
    }

    /**
     * @param array $value list of referenced table column names.
     *
     * @return $this
     */
    public function foreignColumnNames(array $value): self
    {
        $this->foreignColumnNames = $value;

        return $this;
    }

    /**
     * @param string|null $value referential action if rows in a referenced table are to be updated.
     *
     * @return $this
     */
    public function onUpdate(?string $value): self
    {
        $this->onUpdate = $value;

        return $this;
    }

    /**
     * @param string|null $value referential action if rows in a referenced table are to be deleted.
     *
     * @return $this
     */
    public function onDelete(?string $value): self
    {
        $this->onDelete = $value;

        return $this;
    }
}
