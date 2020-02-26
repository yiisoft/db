<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraints;

/**
 * ForeignKeyConstraint represents the metadata of a table `FOREIGN KEY` constraint.
 */
class ForeignKeyConstraint extends Constraint
{
    /**
     * @var string|null referenced table schema name.
     */
    private ?string $foreignSchemaName = null;

    /**
     * @var object|string|null referenced table name.
     */
    private $foreignTableName;

    /**
     * @var array|string list of referenced table column names.
     */
    private $foreignColumnNames;

    /**
     * @var string|null referential action if rows in a referenced table are to be updated.
     */
    private ?string $onUpdate = null;

    /**
     * @var string|null referential action if rows in a referenced table are to be deleted.
     */
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

    public function setForeignSchemaName(?string $value): void
    {
        $this->foreignSchemaName = $value;
    }

    public function setForeignTableName($value): void
    {
        $this->foreignTableName = $value;
    }

    public function setForeignColumnNames($value): void
    {
        $this->foreignColumnNames = $value;
    }

    public function setOnUpdate(?string $value): void
    {
        $this->onUpdate = $value;
    }

    public function setOnDelete(?string $value): void
    {
        $this->onDelete = $value;
    }
}
