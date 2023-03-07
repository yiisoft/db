<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Represents a foreign key constraint in a database.
 *
 * A foreign key constraint is a constraint that enforces referential integrity between two tables.
 *
 * It has information about the table and column(s) that the constraint applies to, as well as any actions that
 * should be taken when a referenced record is deleted or updated.
 */
final class ForeignKeyConstraint extends Constraint
{
    private string|null $foreignSchemaName = null;
    private string|null $foreignTableName = null;
    private array $foreignColumnNames = [];
    private string|null $onUpdate = null;
    private string|null $onDelete = null;

    /**
     * @return string|null The foreign table schema name.
     */
    public function getForeignSchemaName(): string|null
    {
        return $this->foreignSchemaName;
    }

    /**
     * @return string|null The foreign table name.
     */
    public function getForeignTableName(): string|null
    {
        return $this->foreignTableName;
    }

    /**
     * @return array The list of foreign table column names.
     */
    public function getForeignColumnNames(): array
    {
        return $this->foreignColumnNames;
    }

    /**
     * @return string|null The referential action if rows in a referenced table are to be updated.
     */
    public function getOnUpdate(): string|null
    {
        return $this->onUpdate;
    }

    /**
     * @return string|null The referential action if rows in a referenced table are to be deleted.
     */
    public function getOnDelete(): string|null
    {
        return $this->onDelete;
    }

    /**
     * Set the foreign table schema name.
     *
     * @param string|null $value the referenced table schema name.
     */
    public function foreignSchemaName(string|null $value): self
    {
        $this->foreignSchemaName = $value;
        return $this;
    }

    /**
     * Set the foreign table name.
     *
     * @param string|null $value The referenced table name.
     */
    public function foreignTableName(string|null $value): self
    {
        $this->foreignTableName = $value;
        return $this;
    }

    /**
     * Set the list of foreign table column names.
     *
     * @param array $value The list of referenced table column names.
     */
    public function foreignColumnNames(array $value): self
    {
        $this->foreignColumnNames = $value;
        return $this;
    }

    /**
     * Set the referential action if rows in a referenced table are to be updated.
     *
     * @param string|null $value The referential action if rows in a referenced table are to be updated.
     */
    public function onUpdate(string|null $value): self
    {
        $this->onUpdate = $value;
        return $this;
    }

    /**
     * Set the referential action if rows in a referenced table are to be deleted.
     *
     * @param string|null $value The referential action if rows in a referenced table are to be deleted.
     */
    public function onDelete(string|null $value): self
    {
        $this->onDelete = $value;
        return $this;
    }
}
