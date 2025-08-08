<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

/**
 * Represents a column with collation.
 *
 * Provides methods to set and get a collation value for the column.
 */
interface CollatableColumnInterface
{
    /**
     * Sets the collation for the column.
     */
    public function collation(string|null $collation): static;

    /**
     * Returns the collation of the column.
     *
     * @psalm-mutation-free
     */
    public function getCollation(): string|null;
}
