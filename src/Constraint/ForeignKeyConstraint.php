<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * The ForeignKeyConstraint is a class that represents a foreign key constraint in a database. It contains information
 * about the table and column(s) that the constraint applies to, as well as any actions that should be taken when a
 * referenced record is deleted or updated. You can use this class to create and modify foreign key constraints in a
 * database, as well as to retrieve information about existing constraints.
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
     */
    public function foreignSchemaName(string|null $value): self
    {
        $this->foreignSchemaName = $value;

        return $this;
    }

    /**
     * @param string|null $value The referenced table name.
     */
    public function foreignTableName(string|null $value): self
    {
        $this->foreignTableName = $value;

        return $this;
    }

    /**
     * @param array $value The list of referenced table column names.
     */
    public function foreignColumnNames(array $value): self
    {
        $this->foreignColumnNames = $value;

        return $this;
    }

    /**
     * @param string|null $value The referential action if rows in a referenced table are to be updated.
     */
    public function onUpdate(string|null $value): self
    {
        $this->onUpdate = $value;

        return $this;
    }

    /**
     * @param string|null $value The referential action if rows in a referenced table are to be deleted.
     */
    public function onDelete(string|null $value): self
    {
        $this->onDelete = $value;

        return $this;
    }
}
