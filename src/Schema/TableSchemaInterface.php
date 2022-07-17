<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

interface TableSchemaInterface
{
    /**
     * Gets the named column metadata.
     *
     * This is a convenient method for retrieving a named column even if it does not exist.
     *
     * @param string $name column name
     *
     * @return ColumnSchemaInterface|null metadata of the named column. Null if the named column does not exist.
     */
    public function getColumn(string $name): ?ColumnSchemaInterface;

    /**
     * Returns the names of all columns in this table.
     *
     * @return array list of column names
     */
    public function getColumnNames(): array;

    /**
     * @return string|null the name of the schema that this table belongs to.
     */
    public function getSchemaName(): ?string;

    /**
     * @return string the name of this table. The schema name is not included. Use {@see fullName} to get the name with
     * schema name prefix.
     */
    public function getName(): string;

    /**
     * @return string|null the full name of this table, which includes the schema name prefix, if any. Note that if the
     * schema name is the same as the {@see Schema::defaultSchema|default schema name}, the schema name will not be
     * included.
     */
    public function getFullName(): ?string;

    /**
     * @return string|null sequence name for the primary key. Null if no sequence.
     */
    public function getSequenceName(): ?string;

    /**
     * @return array primary keys of this table.
     *
     * @psalm-return string[]
     */
    public function getPrimaryKey(): array;

    /**
     * @return array column metadata of this table. Each array element is a {@see ColumnSchemaInterface} object, indexed by
     * column names.
     *
     * @psalm-return ColumnSchemaInterface[]
     */
    public function getColumns(): array;

    /**
     * Set the name of the schema that this table belongs to.
     *
     * @param string|null $value
     */
    public function schemaName(?string $value): void;

    /**
     * Set name of this table
     *
     * @param string $value
     */
    public function name(string $value): void;

    /**
     * Set the full name of this table, which includes the schema name prefix, if any.
     *
     * @param string|null $value
     */
    public function fullName(?string $value): void;

    /**
     * Set sequence name for the primary key
     *
     * @param string|null $value
     */
    public function sequenceName(?string $value): void;

    /**
     * Set primary keys of this table.
     *
     * @param string $value
     */
    public function primaryKey(string $value): void;

    /**
     * Set one column metadata of this table
     *
     * @param string $index
     * @param ColumnSchemaInterface $value
     */
    public function columns(string $index, ColumnSchemaInterface $value): void;

    /**
     * @return string|null name of the catalog (database) that this table belongs to. Defaults to null, meaning no
     * catalog (or the current database).
     * Specifically for MS SQL Server
     */
    public function getCatalogName(): ?string;

    /**
     * @param string|null set name of the catalog (database) that this table belongs to. Defaults to null, meaning no
     * catalog (or the current database).
     */
    public function catalogName(?string $value): void;

    /**
     * @return string|null name of the server
     * Specifically for MS SQL Server
     */
    public function getServerName(): ?string;

    /**
     * @param string|null set name of the server
     */
    public function serverName(?string $value): void;

    /**
     * @return string|null return sql for create current table or null if query not found/exists
     * Now supported only in MySQL and Oracle
     */
    public function getCreateSql(): ?string;

    /**
     * @param string $sql
     */
    public function createSql(string $sql): void;

    /**
     * ```php
     * [
     *  'ForeignTableName',
     *  'fk1' => 'pk1',  // pk1 is in foreign table
     *  'fk2' => 'pk2',  // if composite foreign key
     * ]
     * ```
     *
     * @return array foreign keys of this table. Each array element is of the following structure:
     * @psalm-return array<array-key, array>
     */
    public function getForeignKeys(): array;

    /**
     * Set foreign keys of this table
     *
     * @psalm-param array<array-key, array> $value
     */
    public function foreignKeys(array $value): void;

    /**
     * Set one foreignKey by index
     *
     * @param int|string $id
     * @param array $to
     */
    public function foreignKey(string|int $id, array $to): void;

    public function compositeFK(int $id, string $from, string $to): void;
}
