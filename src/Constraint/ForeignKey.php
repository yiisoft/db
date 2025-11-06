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
final class ForeignKey
{
    /**
     * @param string $name The constraint name.
     * @param string[] $columnNames The list of column names the constraint belongs to.
     * @param string $foreignSchemaName The referenced schema name.
     * @param string $foreignTableName The referenced table name.
     * @param string[] $foreignColumnNames The list of referenced table column names.
     * @param string|null $onDelete The referential action if rows in a referenced table are to be deleted.
     * @param string|null $onUpdate The referential action if rows in a referenced table are to be updated.
     *
     * @psalm-param ReferentialAction::*|null $onDelete
     * @psalm-param ReferentialAction::*|null $onUpdate
     */
    public function __construct(
        public readonly string $name = '',
        public readonly array $columnNames = [],
        public readonly string $foreignSchemaName = '',
        public readonly string $foreignTableName = '',
        public readonly array $foreignColumnNames = [],
        public readonly ?string $onDelete = null,
        public readonly ?string $onUpdate = null,
    ) {}
}
