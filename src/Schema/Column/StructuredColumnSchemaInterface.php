<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

/**
 * Should be implemented by classes representing the metadata of a structured column (e.g. composite type in PostrgeSQL).
 */
interface StructuredColumnSchemaInterface extends ColumnSchemaInterface
{
    /**
     * Set columns of the composite type.
     *
     * @param ColumnSchemaInterface[] $columns The metadata of the composite type columns.
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     */
    public function columns(array $columns): static;

    /**
     * Get the metadata of the composite type columns.
     *
     * @return ColumnSchemaInterface[]
     */
    public function getColumns(): array;
}
