<?php

namespace Yiisoft\Db;

/**
 * Constraint represents the metadata of a table constraint.
 */
class Constraint
{
    /**
     * @var string[]|null list of column names the constraint belongs to.
     */
    public $columnNames = [];

    /**
     * @var string|null the constraint name.
     */
    public $name;
}
