<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraints;

/**
 * DefaultValueConstraint represents the metadata of a table `DEFAULT` constraint.
 */
class DefaultValueConstraint extends Constraint
{
    private $value;

    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed default value as returned by the DBMS.
     *
     * @return void
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }
}
