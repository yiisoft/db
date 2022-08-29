<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * ForeignKeyConstraint represents the metadata of a table `FOREIGN KEY` constraint.
 */
final class ForeignKeyConstraint extends Constraint
{
    private string|null $foreignSchemaName = null;
    private string|null $foreignTableName = null;
    private array $foreignColumnNames = [];
    private string|null $onUpdate = null;
    private string|null $onDelete = null;

    public function getForeignSchemaName(): string|null
    {
        return $this->foreignSchemaName;
    }

    public function getForeignTableName(): string|null
    {
        return $this->foreignTableName;
    }

    public function getForeignColumnNames(): array
    {
        return $this->foreignColumnNames;
    }

    public function getOnUpdate(): string|null
    {
        return $this->onUpdate;
    }

    public function getOnDelete(): string|null
    {
        return $this->onDelete;
    }

    /**
     * @param string|null $value the referenced table schema name.
     *
     * @return self
     */
    public function foreignSchemaName(string|null $value): self
    {
        $this->foreignSchemaName = $value;

        return $this;
    }

    /**
     * @param string|null $value The referenced table name.
     *
     * @return self
     */
    public function foreignTableName(string|null $value): self
    {
        $this->foreignTableName = $value;

        return $this;
    }

    /**
     * @param array $value The list of referenced table column names.
     *
     * @return self
     */
    public function foreignColumnNames(array $value): self
    {
        $this->foreignColumnNames = $value;

        return $this;
    }

    /**
     * @param string|null $value The referential action if rows in a referenced table are to be updated.
     *
     * @return self
     */
    public function onUpdate(string|null $value): self
    {
        $this->onUpdate = $value;

        return $this;
    }

    /**
     * @param string|null $value The referential action if rows in a referenced table are to be deleted.
     *
     * @return self
     */
    public function onDelete(string|null $value): self
    {
        $this->onDelete = $value;

        return $this;
    }
}
