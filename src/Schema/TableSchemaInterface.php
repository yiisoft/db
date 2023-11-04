<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Exception\NotSupportedException;

/**
 * Represents the metadata of a database table.
 *
 * It defines a set of methods to retrieve the table name, schema name, column names, primary key, foreign keys, etc.
 *
 * The information is obtained from the database schema and may vary according to the DBMS type.
 */
interface TableSchemaInterface
{
    /**
     * Gets the named column metadata.
     *
     * This is a convenient method for retrieving a named column even if it doesn't exist.
     *
     * @param string $name The column name.
     *
     * @return ColumnSchemaInterface|null The named column metadata. Null if the named column doesn't exist.
     */
    public function getColumn(string $name): ColumnSchemaInterface|null;

    /**
     * @return array The names of all columns in this table.
     */
    public function getColumnNames(): array;

    /**
     * @return string|null The comment of the table. Null if no comment.
     */
    public function getComment(): string|null;

    /**
     * @return string|null The name of the schema that this table belongs to.
     */
    public function getSchemaName(): string|null;

    /**
     * @return string The name of this table. The schema name isn't included. Use {@see fullName} to get the name with
     * schema name prefix.
     */
    public function getName(): string;

    /**
     * @return string|null The full name of this table, which includes the schema name prefix, if any. Note that if the
     * schema name is the same as the {@see Schema::defaultSchema} schema name, the schema name won't be included.
     */
    public function getFullName(): string|null;

    /**
     * @return string|null The sequence name for the primary key. Null if no sequence.
     */
    public function getSequenceName(): string|null;

    /**
     * @return array The primary key column names.
     *
     * @psalm-return string[]
     */
    public function getPrimaryKey(): array;

    /**
     * @return ColumnSchemaInterface[] The column metadata of this table.
     * Array of {@see ColumnSchemaInterface} objects indexed by column names.
     *
     * @psalm-return array<string, ColumnSchemaInterface>
     */
    public function getColumns(): array;

    /**
     * Set the name of the schema that this table belongs to.
     *
     * @param string|null $value The name of the schema that this table belongs to.
     */
    public function schemaName(string|null $value): void;

    /**
     * Set the name of this table.
     *
     * The schema name isn't included. Use {@see fullName} to set the name with schema name prefix.
     *
     * @param string $value The name of this table.
     */
    public function name(string $value): void;

    /**
     * Set the full name of this table, which includes the schema name prefix, if any. Note that if the schema name is
     * the same as the {@see Schema::defaultSchema} schema name, the schema name won't be included.
     *
     * @param string|null $value The full name of this table.
     */
    public function fullName(string|null $value): void;

    /**
     * Set the comment of the table.
     *
     * Null if no comment. This isn't supported by all DBMS.
     *
     * @param string|null $value The comment of the table.
     */
    public function comment(string|null $value): void;

    /**
     * Set sequence name for the primary key.
     *
     * @param string|null $value The sequence name for the primary key. Null if no sequence.
     */
    public function sequenceName(string|null $value): void;

    /**
     * Set primary keys of this table.
     *
     * @param string $value The primary key column name.
     */
    public function primaryKey(string $value): void;

    /**
     * Set one column metadata of this table.
     *
     * @param string $name The column name.
     */
    public function column(string $name, ColumnSchemaInterface $value): void;

    /**
     * @return string|null The name of the catalog (database) that this table belongs to. Defaults to null, meaning no
     * catalog (or the current database).
     *
     * Specifically for MSSQL Server
     */
    public function getCatalogName(): string|null;

    /**
     * Set name of the catalog (database) that this table belongs to. Defaults to null, meaning no catalog (or the
     * current database). Specifically for MSSQL Server
     *
     * @param string|null $value The name of the catalog (database) that this table belongs to.
     */
    public function catalogName(string|null $value): void;

    /**
     * @return string|null The name of the server that this table belongs to. Defaults to null, meaning no server
     * (or the current server).
     *
     * Specifically for MSSQL Server
     */
    public function getServerName(): string|null;

    /**
     * Set name of the server that this table belongs to. Defaults to null, meaning no server (or the current server).
     * Specifically for MSSQL Server
     *
     * @param string|null $value The name of the server that this table belongs to.
     */
    public function serverName(string|null $value): void;

    /**
     * @return string|null The SQL for create current table or `null` if a query not found/exists. Now supported only in
     * MySQL and Oracle DBMS.
     */
    public function getCreateSql(): string|null;

    /**
     * Set SQL for create current table or null if a query not found/exists. Now supported only in MySQL and Oracle DBMS.
     *
     * @param string $sql The SQL for create current table or `null` if a query not found/exists.
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
     * Set foreign keys of this table.
     *
     * @psalm-param array<array-key, array> $value The foreign keys of this table.
     */
    public function foreignKeys(array $value): void;

    /**
     * Set one foreignKey by index.
     *
     * @param int|string $id The index of foreign key.
     * @param array $to The foreign key.
     */
    public function foreignKey(string|int $id, array $to): void;

    /**
     * Set composite foreign key.
     *
     * @param int $id The index of foreign key.
     * @param string $from The column name in current table.
     * @param string $to The column name in foreign table.
     *
     * @throws NotSupportedException
     *
     * @deprecated will be removed in version 2.0.0
     */
    public function compositeForeignKey(int $id, string $from, string $to): void;
}
