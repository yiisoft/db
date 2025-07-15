<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

use Yiisoft\Db\Constant\ReferentialAction;

/**
 * Represents a foreign key constraint in a database.
 *
 * A foreign key constraint is a constraint that enforces referential integrity between two tables.
 *
 * It has information about the table and column(s) that the constraint applies to, as well as any actions that
 * should be taken when a referenced record is deleted or updated.
 */
final class ForeignKeyConstraint extends AbstractConstraint
{
    /**
     * @param string $name The constraint name.
     * @param string[] $columnNames The list of column names the constraint belongs to.
     * @param string $foreignTableName The referenced table name.
     * @param string[] $foreignColumnNames The list of referenced table column names.
     * @param string|null $onUpdate The referential action if rows in a referenced table are to be updated.
     * @param string|null $onDelete The referential action if rows in a referenced table are to be deleted.
     *
     * @psalm-param ReferentialAction::*|null $onUpdate
     * @psalm-param ReferentialAction::*|null $onDelete
     */
    public function __construct(
        string $name = '',
        array $columnNames = [],
        private string $foreignTableName = '',
        private array $foreignColumnNames = [],
        private string|null $onUpdate = null,
        private string|null $onDelete = null,
    ) {
        parent::__construct($name, $columnNames);
    }

    /**
     * Returns the foreign table name.
     *
     * @psalm-immutable
     */
    public function getForeignTableName(): string
    {
        return $this->foreignTableName;
    }

    /**
     * @return string[] The list of foreign table column names.
     *
     * @psalm-immutable
     */
    public function getForeignColumnNames(): array
    {
        return $this->foreignColumnNames;
    }

    /**
     * Returns the referential action if rows in a referenced table are to be updated.
     *
     * @psalm-return ReferentialAction::*|null
     * @psalm-immutable
     */
    public function getOnUpdate(): string|null
    {
        return $this->onUpdate;
    }

    /**
     * Returns the referential action if rows in a referenced table are to be deleted.
     *
     * @psalm-return ReferentialAction::*|null
     * @psalm-immutable
     */
    public function getOnDelete(): string|null
    {
        return $this->onDelete;
    }

    /**
     * Set the foreign table name.
     *
     * @param string $foreignTableName The referenced table name.
     */
    public function foreignTableName(string $foreignTableName): self
    {
        $this->foreignTableName = $foreignTableName;
        return $this;
    }

    /**
     * Set the list of foreign table column names.
     *
     * @param string[] $foreignColumnNames The list of referenced table column names.
     */
    public function foreignColumnNames(array $foreignColumnNames): self
    {
        $this->foreignColumnNames = $foreignColumnNames;
        return $this;
    }

    /**
     * Set the referential action if rows in a referenced table are to be updated.
     *
     * @param string|null $onUpdate The referential action if rows in a referenced table are to be updated.
     * See {@see ReferentialAction} class for possible values.
     *
     * @psalm-param ReferentialAction::*|null $onUpdate
     */
    public function onUpdate(string|null $onUpdate): self
    {
        $this->onUpdate = $onUpdate;
        return $this;
    }

    /**
     * Set the referential action if rows in a referenced table are to be deleted.
     *
     * @param string|null $onDelete The referential action if rows in a referenced table are to be deleted.
     * See {@see ReferentialAction} class for possible values.
     *
     * @psalm-param ReferentialAction::*|null $onDelete
     */
    public function onDelete(string|null $onDelete): self
    {
        $this->onDelete = $onDelete;
        return $this;
    }
}
