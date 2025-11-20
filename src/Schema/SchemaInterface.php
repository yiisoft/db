<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Constraint\ConstraintSchemaInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * Represents the schema for a database table.
 *
 * It provides a set of methods for working with the schema of a database table, such as accessing the columns,
 * indexes, and constraints of a table, as well as methods for creating, dropping, and altering tables.
 */
interface SchemaInterface extends ConstraintSchemaInterface
{
    /**
     * The metadata type for retrieving the table schema.
     */
    public const SCHEMA = 'schema';
    /**
     * The metadata type for retrieving the primary key constraint.
     */
    public const PRIMARY_KEY = 'primaryKey';
    /**
     * The metadata type for retrieving the index constraints.
     */
    public const INDEXES = 'indexes';
    /**
     * The metadata type for retrieving the check constraint.
     */
    public const CHECKS = 'checks';
    /**
     * The metadata type for retrieving the foreign key constraints.
     */
    public const FOREIGN_KEYS = 'foreignKeys';
    /**
     * The metadata type for retrieving the default values constraint.
     */
    public const DEFAULT_VALUES = 'defaultValues';
    /**
     * The metadata type for retrieving the unique constraints.
     */
    public const UNIQUES = 'uniques';
    /**
     * The metadata type for retrieving the default constraint.
     */
    public const DEFAULTS = 'defaults';

    /**
     * @return string The default schema name.
     */
    public function getDefaultSchema(): string;

    /**
     * Determines the SQL data type for the given PHP data value.
     *
     * @param mixed $data The data to find a type for.
     *
     * @return int The type.
     *
     * @psalm-return DataType::*
     */
    public function getDataType(mixed $data): int;

    /**
     * Returns the column instance for the column metadata received from the query result.
     *
     * @param array $metadata The column metadata from the query result.
     */
    public function getResultColumn(array $metadata): ?ColumnInterface;

    /**
     * Returns all schema names in the database, except system schemas.
     *
     * @param bool $refresh Whether to fetch the latest available schema names. If this is `false`, schema names fetched
     * before (if available) will be returned.
     *
     * @return string[] All schemas name in the database, except system schemas.
     */
    public function getSchemaNames(bool $refresh = false): array;

    /**
     * Returns all table names in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * If not empty, the returned table names will be prefixed with the schema name.
     * @param bool $refresh Whether to fetch the latest available table names. If this is `false`, table names fetched
     * before (if available) will be returned.
     *
     * @return string[] All tables name in the database.
     */
    public function getTableNames(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the metadata for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any.
     * Don't quote the table name.
     * @param bool $refresh Whether to reload the table schema even if it's found in the cache.
     *
     * @return TableSchemaInterface|null Table metadata. `null` if the named table doesn't exist.
     */
    public function getTableSchema(string $name, bool $refresh = false): ?TableSchemaInterface;

    /**
     * Returns the metadata for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @return TableSchemaInterface[] The metadata for all tables in the database.
     */
    public function getTableSchemas(string $schema = '', bool $refresh = false): array;

    /**
     * Refreshes the schema.
     *
     * This method cleans up all cached table schemas so that they can be re-created later to reflect the database
     * schema change.
     */
    public function refresh(): void;

    /**
     * Refreshes the particular table schema.
     *
     * This method cleans up cached table schema so that it can be re-created later to reflect the database schema
     * change.
     *
     * @param string $name Table name.
     */
    public function refreshTableSchema(string $name): void;

    /**
     * Refreshes all cached view names.
     */
    public function refreshSchemaViewNames(): void;

    /**
     * Enable or disable the schema cache.
     *
     * @param bool $value Whether to enable or disable the schema cache.
     */
    public function enableCache(bool $value): void;

    /**
     * Returns all view names in the database.
     *
     * @param string $schema The schema of the views. Defaults to empty string, meaning the current or default schema
     * name. If not empty, the returned view names will be prefixed with the schema name.
     * @param bool $refresh Whether to fetch the latest available view names. If this is false, view names fetched
     * before (if available) will be returned.
     *
     * @return string[] All view names in the database.
     */
    public function getViewNames(string $schema = '', bool $refresh = false): array;

    /**
     * Determines if a specified table exists in the database.
     *
     * @param string $tableName The table name to search for
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name. If not empty, the table will be searched in the specified schema.
     * @param bool $refresh Whether to fetch the latest available table names. If this is false, view names fetched
     * before (if available) will be returned.
     *
     * @return bool Whether table exists.
     */
    public function hasTable(string $tableName, string $schema = '', bool $refresh = false): bool;

    /**
     * Determines if a specified schema exists in the database.
     *
     * @param string $schema The schema name to search for
     * @param bool $refresh Whether to fetch the latest available schema names. If this is false, view names fetched
     * before (if available) will be returned.
     *
     * @return bool Whether schema exists.
     */
    public function hasSchema(string $schema, bool $refresh = false): bool;

    /**
     * Determines if a specified view exists in the database.
     *
     * @param string $viewName The view name to search for
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name. If not empty, the table will be searched in the specified schema.
     * @param bool $refresh Whether to fetch the latest available view names. If this is false, view names fetched
     * before (if available) will be returned.
     *
     * @return bool Whether view exists.
     */
    public function hasView(string $viewName, string $schema = '', bool $refresh = false): bool;
}
