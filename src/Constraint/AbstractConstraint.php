<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Represents an abstract constraint in a database.
 *
 * A constraint is a rule enforced on the data in a table, such as a primary key, a foreign key, a default value,
 * check or index constraint.
 *
 * It's the base class for all constraint classes, and defines methods that are common and shared by all constrained
 * classes, with name and columnNames being the most common ones.
 */
abstract class AbstractConstraint
{
    /**
     * @param string $name The constraint name.
     * @param string[] $columnNames The list of column names the constraint belongs to.
     */
    public function __construct(
        public readonly string $name = '',
        public readonly array $columnNames = [],
    ) {
    }
}
