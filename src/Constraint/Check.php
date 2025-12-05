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
final class Check
{
    /**
     * @param string $name The constraint name.
     * @param string[] $columnNames The list of column names the constraint belongs to.
     * @param string $expression The SQL of the `CHECK` constraint.
     */
    public function __construct(
        public readonly string $name = '',
        public readonly array $columnNames = [],
        public readonly string $expression = '',
    ) {}
}
