<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * DefaultValueConstraint represents the metadata of a table `DEFAULT` constraint.
 */
class DefaultValueConstraint extends Constraint
{
    private mixed $value;

    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed default value as returned by the DBMS.
     *
     * @return $this
     */
    public function value(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }
}
