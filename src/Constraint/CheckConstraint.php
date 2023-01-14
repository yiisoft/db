<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * The CheckConstraint represents a CHECK constraint in a database table, which is used to validate data before it is
 * inserted or updated in the table. The constraint checks that the value of a specified column or expression meets a
 * certain condition. If the condition is not met, an error will be thrown and the data will not be inserted or updated.
 * The CheckConstraint class allows you to specify the condition that must be met, as well as the columns or expressions
 * that the constraint applies to.
 */
final class CheckConstraint extends Constraint
{
    private string $expression = '';

    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @param string $value The SQL of the `CHECK` constraint.
     */
    public function expression(string $value): self
    {
        $this->expression = $value;

        return $this;
    }
}
