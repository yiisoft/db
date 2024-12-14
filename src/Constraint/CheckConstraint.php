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
final class CheckConstraint extends AbstractConstraint
{
    /**
     * @param string $name The constraint name.
     * @param string[] $columnNames The list of column names the constraint belongs to.
     * @param string $expression The SQL of the `CHECK` constraint.
     */
    public function __construct(string $name = '', array $columnNames = [], private string $expression = '')
    {
        parent::__construct($name, $columnNames);
    }

    /**
     * @return string The SQL of the `CHECK` constraint.
     *
     * @psalm-immutable
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Set the SQL of the `CHECK` constraint.
     *
     * @param string $expression The SQL of the `CHECK` constraint.
     */
    public function expression(string $expression): self
    {
        $this->expression = $expression;
        return $this;
    }
}
