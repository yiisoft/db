<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

/**
 * The TableSchemaInterface class that defines the methods that must be implemented by a class representing the schema
 * of a database table.
 *
 * It provides a set of methods for getting metadata about a database table, such as its name, the names and types of
 * its columns, and any constraints or indexes it may have.
 */
interface TableSchemaInterface
{
    /**
     * Gets the named column metadata.
     *
     * This is a convenient method for retrieving a named column even if it does not exist.
     *
     * @param string $name The column name.
     *
     * @return ColumnSchemaInterface|null The named column metadata. `null` if the named column does not exist.
     */
    public function getColumn(string $name): ColumnSchemaInterface|null;

    /**
     * @return array The names of all columns in this table.
     */
    public function getColumnNames(): array;

    /**
     * @return string|null The comment of this table. Not all DBMS support this.
     */
    public function getComment(): string|null;

    /**
     * @return string|null The name of the schema that this table belongs to.
     */
    public function getSchemaName(): string|null;

    /**
     * @return string The name of this table. The schema name is not included. Use {@see fullName} to get the name with
     * schema name prefix.
     */
    public function getName(): string;

    /**
     * @return string|null The full name of this table, which includes the schema name prefix, if any. Note that if the
     * schema name is the same as the {@see Schema::defaultSchema|default schema name}, the schema name will not be
     * included.
     */
    public function getFullName(): string|null;

    /**
     * @return string|null The sequence name for the primary key. `null` if no sequence.
     */
    public function getSequenceName(): string|null;

    /**
     * @return array The primary keys of this table.
     *
     * @psalm-return string[]
     */
    public function getPrimaryKey(): array;

    /**
     * @return array The column metadata of this table. Each array element is a {@see ColumnSchemaInterface} object,
     * indexed by column names.
     *
     * @psalm-return ColumnSchemaInterface[]
     */
    public function getColumns(): array;

    /**
     * Set the name of the schema that this table belongs to.
     *
     * @param string|null $value The name of the schema that this table belongs to.
     */
    public function schemaName(string|null $value): void;

    /**
     * Set name of this table.
     *
     * @param string $value The name of this table. The schema name is not included. Use {@see fullName} to get the name
     * with schema name prefix.
     */
    public function name(string $value): void;

    /**
     * Set the full name of this table, which includes the schema name prefix, if any.
     *
     * Note that if the schema name is the same as the {@see Schema::defaultSchema|default schema name}, the schema name
     * will not be included.
     *
     * @param string|null $value The full name of this table, which includes the schema name prefix, if any.
     */
    public function fullName(string|null $value): void;

    /**
     * Set the comment of this table. Not all DBMS support this.
     *
     * @param string|null $value The comment of this table. Not all DBMS support this.
     */
    public function comment(string|null $value): void;

    /**
     * Set sequence name for the primary key.
     *
     * @param string|null $value The sequence name for the primary key. `null` if no sequence.
     */
    public function sequenceName(string|null $value): void;

    /**
     * Set primary keys of this table.
     *
     * @param string $value The primary keys of this table.
     */
    public function primaryKey(string $value): void;

    /**
     * Set one column metadata of this table
     *
     * @param string $index The column name.
     * @param ColumnSchemaInterface $value The column metadata.
     */
    public function columns(string $index, ColumnSchemaInterface $value): void;

    /**
     * @return string|null The name of the catalog (database) that this table belongs to. Defaults to null, meaning
     * no catalog (or the current database). Specifically for MS SQL Server.
     */
    public function getCatalogName(): string|null;

    /**
     * Set name of the catalog (database) that this table belongs to.
     *
     * @param string|null $value The name of the catalog (database) that this table belongs to. Defaults to null,
     * meaning no catalog (or the current database). Specifically for MS SQL Server.
     */
    public function catalogName(string|null $value): void;

    /**
     * @return string|null The name of the server. Specifically for MS SQL Server
     */
    public function getServerName(): string|null;

    /**
     * Set name of the server.
     *
     * @param string|null $value The name of the server. Specifically for MS SQL Server
     */
    public function serverName(string|null $value): void;

    /**
     * @return string|null The Sql for create current table or null if query not found/exists Now supported only in
     * MySQL and Oracle.
     */
    public function getCreateSql(): string|null;

    /**
     * Set Sql for create current table or null if query not found/exists Now supported only in MySQL and Oracle.
     *
     * @param string $sql The Sql for create current table or null if query not found/exists Now supported only in
     * MySQL and Oracle.
     */
    public function createSql(string $sql): void;

    /**
     * @return array The foreign keys of this table. Each array element is of the following structure:
     *
     * ```php
     * [
     *  'ForeignTableName',
     *  'fk1' => 'pk1',  // pk1 is in foreign table
     *  'fk2' => 'pk2',  // if composite foreign key
     * ]
     * ```
     *
     * @psalm-return array<array-key, array>
     */
    public function getForeignKeys(): array;

    /**
     * Set foreign keys of this table
     *
     * @param array $value The foreign keys of this table.
     *
     * @psalm-param array<array-key, array> $value
     */
    public function foreignKeys(array $value): void;

    /**
     * Set one foreignKey by index.
     *
     * @param int|string $id The index of foreign key.
     */
    public function foreignKey(string|int $id, array $to): void;

    /**
     * Set composite foreign key.
     *
     * @param int $id The index of foreign key.
     * @param string $from The column name in current table.
     * @param string $to The column name in foreign table.
     */
    public function compositeFK(int $id, string $from, string $to): void;
}
