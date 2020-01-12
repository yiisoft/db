<?php

namespace Yiisoft\Db;

/**
 * DefaultValueConstraint represents the metadata of a table `DEFAULT` constraint.
 */
class DefaultValueConstraint extends Constraint
{
    /**
     * @var mixed default value as returned by the DBMS.
     */
    public $value;
}
