<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constant;

/**
 * `ReferentialAction` represents the possible actions that can be performed on the referenced table.
 *
 * The constants are used to specify `ON DELETE` and `ON UPDATE` actions by {@see ForeignKeyConstraint::onDelete()},
 * {@see ForeignKeyConstraint::onUpdate()}, {@see DDLQueryBuilderInterface::addForeignKey()}
 * and {@see CommandInterface::addForeignKey()}.
 *
 * MSSQL does not support `RESTRICT` key world but uses this behavior by default (if no action is specified).
 *
 * Oracle supports only `CASCADE` and `SET NULL` key worlds and only `ON DELETE` clause.
 * `NO ACTION` is used by default for `ON DELETE` and `ON UPDATE` (if no action is specified).
 */
final class ReferentialAction
{
    /**
     * @var string Used when the referenced rows are not deleted or updated. If rows of the referenced table are used
     * by rows of the referencing table, an error is thrown. This is the default behavior.
     */
    public const NO_ACTION = 'NO ACTION';
    /**
     * @var string Used when the referenced rows cannot be deleted or updated. For example, if rows of the referenced
     * table are used by rows of the referencing table, it is not permitted to delete those rows from the referenced table.
     * This is similar to {@see NO_ACTION}, but is not used by some DBMSs.
     */
    public const RESTRICT = 'RESTRICT';
    /**
     * @var string Used when the referenced row is deleted or updated and all rows that reference it are deleted
     * or updated accordingly.
     */
    public const CASCADE = 'CASCADE';
    /**
     * @var string Used when the rows that refer to the deleted or updated row are set to `NULL`.
     * If columns are defined as `NOT NULL`, an error is thrown.
     */
    public const SET_NULL = 'SET NULL';
    /**
     * @var string Used when the rows that refer to the deleted or updated row are set to their default values.
     */
    public const SET_DEFAULT = 'SET DEFAULT';
}
