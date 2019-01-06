<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

/**
 * ForeignKeyConstraint represents the metadata of a table `FOREIGN KEY` constraint.
 *
 * @author Sergey Makinen <sergey@makinen.ru>
 * @since 2.0.13
 */
class ForeignKeyConstraint extends Constraint
{
    /**
     * @var string|null referenced table schema name.
     */
    private $foreignSchemaName;
    /**
     * @var string referenced table name.
     */
    private $foreignTableName;
    /**
     * @var string[] list of referenced table column names.
     */
    private $foreignColumnNames;
    /**
     * @var string|null referential action if rows in a referenced table are to be updated.
     */
    private $onUpdate;
    /**
     * @var string|null referential action if rows in a referenced table are to be deleted.
     */
    private $onDelete;

    /**
     * Constructor
     * @param string|null name (inherited from parent) the constraint name.
     * @param string[]|null columnNames (inherited from parent) list of column names the constraint belongs to.
     * @param string|null foreignSchemaName referenced table schema name.
     * @param string foreignTableName referenced table name.
     * @param string[] foreignColumnNames list of referenced table column names.
     * @param string|null onUpdate referential action if rows in a referenced table are to be updated.
     * @param string|null onDelete referential action if rows in a referenced table are to be deleted.
     */
    public function __construct(?string $name, ?iterable $columnNames, ?string $foreignSchemaName, string $foreignTableName, iterable $foreignColumnNames, ?string $onDelete, ?string $onUpdate)
    {
        parent::__construct($name, $columnNames);
        $this->foreignSchemaName = $foreignSchemaName;
        $this->foreignTableName = $foreignTableName;
        $this->foreignColumnNames = $foreignColumnNames;
        $this->onDelete = $onDelete;
        $this->onUpdate = $onUpdate;
    }

    public function getForeignSchemaName() : ?string
    {
        return $this->foreignSchemaName;
    }
    public function getForeignTableName() : ?string
    {
        return $this->foreignTableName;
    }
    public function getForeignColumnNames() : iterable
    {
        return $this->foreignColumnNames;
    }
    public function getOnUpdate() : ?string
    {
        return $this->onUpdate;
    }
    public function getOnDelete() : ?string
    {
        return $this->onDelete;
    }

}

