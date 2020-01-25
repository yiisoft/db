<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraints;

/**
 * DefaultValueConstraint represents the metadata of a table `DEFAULT` constraint.
 */
class DefaultValueConstraint extends Constraint
{
    /**
     * @var mixed default value as returned by the DBMS.
     */
    private $value;

    public function getValue()
    {
        return $this->value;
    }

    public function setValue(): void
    {
        $this->value = $value;
    }
}
