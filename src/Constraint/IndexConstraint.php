<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Represents an index constraint in a database.
 *
 * An index constraint is a constraint that enforces uniqueness or non-uniqueness of a column or a set of columns.
 *
 * It has information about the table and column(s) that the constraint applies to, as well as whether the index is
 * unique.
 */
final class IndexConstraint extends AbstractConstraint
{
    /**
     * @param string $name The constraint name.
     * @param string[] $columnNames The list of column names the constraint belongs to.
     * @param bool $isUnique Whether the index is unique.
     * @param bool $isPrimaryKey Whether the index was created for a primary key.
     */
    public function __construct(
        string $name = '',
        array $columnNames = [],
        private bool $isUnique = false,
        private bool $isPrimaryKey = false,
    ) {
        parent::__construct($name, $columnNames);
    }

    /**
     * @return bool Whether the index is unique.
     *
     * @psalm-immutable
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * @return bool Whether the index was created for a primary key.
     *
     * @psalm-immutable
     */
    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    /**
     * Set whether the index is unique.
     *
     * @param bool $isUnique Whether the index is unique.
     */
    public function unique(bool $isUnique = true): self
    {
        $this->isUnique = $isUnique;
        return $this;
    }

    /**
     * Set whether the index was created for a primary key.
     *
     * @param bool $isPrimaryKey whether the index was created for a primary key.
     */
    public function primaryKey(bool $isPrimaryKey = true): self
    {
        $this->isPrimaryKey = $isPrimaryKey;
        return $this;
    }
}
