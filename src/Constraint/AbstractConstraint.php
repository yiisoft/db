<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Represents an abstract constraint in a database.
 *
 * A constraint is a rule that's enforced on the data in a table, such as a primary key, a foreign key, a default value,
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
    public function __construct(private string $name = '', private array $columnNames = [])
    {
    }

    /**
     * @return string[] The list of column names the constraint belongs to.
     *
     * @psalm-immutable
     */
    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    /**
     * @return string The constraint name.
     *
     * @psalm-immutable
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the list of column names the constraint belongs to.
     *
     * @param string[] $value The list of column names the constraint belongs to.
     */
    public function columnNames(array $value): static
    {
        $this->columnNames = $value;
        return $this;
    }

    /**
     * Set the constraint name.
     *
     * @param string $value The constraint name.
     */
    public function name(string $value): static
    {
        $this->name = $value;
        return $this;
    }
}
