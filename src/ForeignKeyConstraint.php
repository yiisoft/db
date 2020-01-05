<?php
declare(strict_types=1);

namespace Yiisoft\Db;

/**
 * ForeignKeyConstraint represents the metadata of a table `FOREIGN KEY` constraint.
 */
class ForeignKeyConstraint extends Constraint
{
    /**
     * @var string|null referenced table schema name.
     */
    public $foreignSchemaName;

    /**
     * @var string referenced table name.
     */
    public $foreignTableName;

    /**
     * @var string[] list of referenced table column names.
     */
    public $foreignColumnNames;

    /**
     * @var string|null referential action if rows in a referenced table are to be updated.
     */
    public $onUpdate;

    /**
     * @var string|null referential action if rows in a referenced table are to be deleted.
     */
    public $onDelete;
}
