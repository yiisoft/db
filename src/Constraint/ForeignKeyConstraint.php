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
     * @return void
     */
    public function setForeignSchemaName(?string $value): void
    {
        $this->foreignSchemaName = $value;
    }

    /**
     * @param object|string|null $value referenced table name.
     *
     * @return void
     */
    public function setForeignTableName($value): void
    {
        $this->foreignTableName = $value;
    }

    /**
     * @param array|string $value list of referenced table column names.
     *
     * @return void
     */
    public function setForeignColumnNames($value): void
    {
        $this->foreignColumnNames = $value;
    }

    /**
     * @param string|null $value referential action if rows in a referenced table are to be updated.
     *
     * @return void
     */
    public function setOnUpdate(?string $value): void
    {
        $this->onUpdate = $value;
    }

    /**
     * @param string|null $value referential action if rows in a referenced table are to be deleted.
     *
     * @return void
     */
    public function setOnDelete(?string $value): void
    {
        $this->onDelete = $value;
    }
}
