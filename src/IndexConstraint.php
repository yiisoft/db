<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

/**
 * IndexConstraint represents the metadata of a table `INDEX` constraint.
 *
 * @author Sergey Makinen <sergey@makinen.ru>
 * @since 2.0.13
 */
class IndexConstraint extends Constraint
{
    /**
     * @var bool whether the index is unique.
     */
    private $isUnique;
    /**
     * @var bool whether the index was created for a primary key.
     */
    private $isPrimary;

    /**
     * Constructor
     * @param bool isPrimary whether the index was created for a primary key.
     * @param bool isUnique whether the index is unique.
     * @param string|null name (inherited from parent) the constraint name.
     * @param string[]|null columnNames (inherited from parent) list of column names the constraint belongs to.
     */
    public function __construct(bool $isPrimary, bool $isUnique, ?string $name, ?iterable $columnNames)
    {
        parent::__construct($name, $columnNames);
        $this->isPrimary = $isPrimary;
        $this->isUnique = $isUnique;
    }

    public function getIsUnique() : bool
    {
        return $this->isUnique;
    }
    public function getIsPrimary() : bool
    {
        return $this->isPrimary;
    }    
}
