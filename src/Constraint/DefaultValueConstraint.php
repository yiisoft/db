<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * DefaultValueConstraint represents the metadata of a table `DEFAULT` constraint.
 */
class DefaultValueConstraint extends Constraint
{
    /** @var mixed */
    private $value;

    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed default value as returned by the DBMS.
     *
     * @return $this
     */
    public function value($value): self
    {
        $this->value = $value;

        return $this;
    }
}
