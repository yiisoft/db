<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

use yii\base\BaseObject;

/**
 * Constraint represents the metadata of a table constraint.
 *
 * @author Sergey Makinen <sergey@makinen.ru>
 * @since 2.0.13
 */
class Constraint extends BaseObject
{
    /**
     * @var string[]|null list of column names the constraint belongs to.
     */
    private $columnNames;
    /**
     * @var string|null the constraint name.
     */
    private $name;

    /**
     * Constructor
     * @param string|null $name the constraint name.
     * @param string[]|null $columnNames list of column names the constraint belongs to.
     */
    public function __construct(?string $name, ?iterable $columnNames)
    {
        $this->name = $name;
        $this->columnNames = $columnNames;
    }

    public function getColumnNames() : ?iterable 
    {
        return $this->columnNames;
    }
    public function getName() : ?string
    {
        return $this->name;
    }
}
