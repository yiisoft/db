<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Represents a `CHECK` constraint in a database.
 *
 * A `CHECK` constraint is a constraint that allows you to specify a condition that must be met for the data to be
 * inserted or updated.
 *
 * The constraint checks that the value of a specified column or expression meets a certain condition, if the condition
 * isn't met, an error will be thrown, and the data won't be inserted or updated.
 */
final class CheckConstraint extends Constraint
{
    private string $expression = '';

    /**
     * @return string The SQL of the `CHECK` constraint.
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Set the SQL of the `CHECK` constraint.
     *
     * @param string $value The SQL of the `CHECK` constraint.
     */
    public function expression(string $value): self
    {
        $this->expression = $value;
        return $this;
    }
}
