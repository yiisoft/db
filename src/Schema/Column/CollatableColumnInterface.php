<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

/**
 *
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
