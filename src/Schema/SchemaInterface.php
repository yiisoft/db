<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
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
     * Define the type of the index as `UNIQUE`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MySQL`, `MariaDB`, `MSSQL`, `Oracle`, `PostgreSQL`, `SQLite`.
     *
     * @deprecated Use {@see IndexType::UNIQUE} instead. Will be removed in 2.0.
     */
    public const INDEX_UNIQUE = 'UNIQUE';
    /**
     * Define the type of the index as `BTREE`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MySQL`, `PostgreSQL`.
     *
     * @deprecated Use {@see IndexType::BTREE} instead. Will be removed in 2.0.
     */
    public const INDEX_BTREE = 'BTREE';
    /**
     * Define the type of the index as `HASH`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MySQL`, `PostgreSQL`.
     *
     * @deprecated Use {@see IndexType::HASH} instead. Will be removed in 2.0.
     */
    public const INDEX_HASH = 'HASH';
    /**
     * Define the type of the index as `FULLTEXT`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MySQL`.
     *
     * @deprecated Use {@see IndexType::FULLTEXT} instead. Will be removed in 2.0.
     */
    public const INDEX_FULLTEXT = 'FULLTEXT';
    /**
     * Define the type of the index as `SPATIAL`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MySQL`.
     *
     * @deprecated Use {@see IndexType::SPATIAL} instead. Will be removed in 2.0.
     */
    public const INDEX_SPATIAL = 'SPATIAL';
    /**
     * Define the type of the index as `GIST`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `PostgreSQL`.
     *
     * @deprecated Use {@see IndexMethod::GIST} instead. Will be removed in 2.0.
     */
    public const INDEX_GIST = 'GIST';
    /**
     * Define the type of the index as `GIN`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `PostgreSQL`.
     *
     * @deprecated Use {@see IndexMethod::GIN} instead. Will be removed in 2.0.
     */
    public const INDEX_GIN = 'GIN';
    /**
     * Define the type of the index as `BRIN`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `PostgreSQL`.
     *
     * @deprecated Use {@see IndexMethod::BRIN} instead. Will be removed in 2.0.
     */
    public const INDEX_BRIN = 'BRIN';
    /**
     * Define the type of the index as `CLUSTERED`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MSSQL`.
     *
     * @deprecated Use {@see IndexType::CLUSTERED} instead. Will be removed in 2.0.
     */
    public const INDEX_CLUSTERED = 'CLUSTERED';
    /**
     * Define the type of the index as `NONCLUSTERED`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MSSQL`.
     *
     * @deprecated Use {@see IndexType::NONCLUSTERED} instead. Will be removed in 2.0.
     */
    public const INDEX_NONCLUSTERED = 'NONCLUSTERED';
    /**
     * Define the type of the index as `BITMAP`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `Oracle`.
     *
     * @deprecated Use {@see IndexType::BITMAP} instead. Will be removed in 2.0.
     */
    public const INDEX_BITMAP = 'BITMAP';
    /**
     * Define the abstract column type as a primary key.
     *
     * @deprecated Use {@see PseudoType::PK} instead. Will be removed in 2.0.
     */
    public const TYPE_PK = 'pk';
    /**
     * Define the abstract column type as an `unsigned` primary key.
     *
     * @deprecated Use {@see PseudoType::UPK} instead. Will be removed in 2.0.
     */
    public const TYPE_UPK = 'upk';
    /**
     * Define the abstract column type as big primary key.
     *
     * @deprecated Use {@see PseudoType::BIGPK} instead. Will be removed in 2.0.
     */
    public const TYPE_BIGPK = 'bigpk';
    /**
     * Define the abstract column type as `unsigned` big primary key.
     *
     * @deprecated Use {@see PseudoType::UBIGPK} instead. Will be removed in 2.0.
     */
    public const TYPE_UBIGPK = 'ubigpk';
    /**
     * Define the abstract column type as an `uuid` primary key.
     *
     * @deprecated Use {@see PseudoType::UUID_PK} instead. Will be removed in 2.0.
     */
    public const TYPE_UUID_PK = 'uuid_pk';
    /**
     * Define the abstract column type as an`uuid` primary key with a sequence.
     *
     * @deprecated Use {@see PseudoType::UUID_PK_SEQ} instead. Will be removed in 2.0.
     */
    public const TYPE_UUID_PK_SEQ = 'uuid_pk_seq';
    /**
     * Define the abstract column type as `uuid`.
     *
     * @deprecated Use {@see ColumnType::UUID} instead. Will be removed in 2.0.
     */
    public const TYPE_UUID = 'uuid';
    /**
     * Define the abstract column type as `char`.
     *
     * @deprecated Use {@see ColumnType::CHAR} instead. Will be removed in 2.0.
     */
    public const TYPE_CHAR = 'char';
    /**
     * Define the abstract column type as `string`.
     *
     * @deprecated Use {@see ColumnType::STRING} instead. Will be removed in 2.0.
     */
    public const TYPE_STRING = 'string';
    /**
     * Define the abstract column type as `text`.
     *
     * @deprecated Use {@see ColumnType::TEXT} instead. Will be removed in 2.0.
     */
    public const TYPE_TEXT = 'text';
    /**
     * Define the abstract column type as `tinyint`.
     *
     * @deprecated Use {@see ColumnType::TINYINT} instead. Will be removed in 2.0.
     */
    public const TYPE_TINYINT = 'tinyint';
    /**
     * Define the abstract column type as `smallint`.
     *
     * @deprecated Use {@see ColumnType::SMALLINT} instead. Will be removed in 2.0.
     */
    public const TYPE_SMALLINT = 'smallint';
    /**
     * Define the abstract column type as `integer`.
     *
     * @deprecated Use {@see ColumnType::INTEGER} instead. Will be removed in 2.0.
     */
    public const TYPE_INTEGER = 'integer';
    /**
     * Define the abstract column type as `bigint`.
     *
     * @deprecated Use {@see ColumnType::BIGINT} instead. Will be removed in 2.0.
     */
    public const TYPE_BIGINT = 'bigint';
    /**
     * Define the abstract column type as `float`.
     *
     * @deprecated Use {@see ColumnType::FLOAT} instead. Will be removed in 2.0.
     */
    public const TYPE_FLOAT = 'float';
    /**
     * Define the abstract column type as `double`.
     *
     * @deprecated Use {@see ColumnType::DOUBLE} instead. Will be removed in 2.0.
     */
    public const TYPE_DOUBLE = 'double';
    /**
     * Define the abstract column type as `decimal`.
     *
     * @deprecated Use {@see ColumnType::DECIMAL} instead. Will be removed in 2.0.
     */
    public const TYPE_DECIMAL = 'decimal';
    /**
     * Define the abstract column type as `datetime`.
     *
     * @deprecated Use {@see ColumnType::DATETIME} instead. Will be removed in 2.0.
     */
    public const TYPE_DATETIME = 'datetime';
    /**
     * Define the abstract column type as `timestamp`.
     *
     * @deprecated Use {@see ColumnType::TIMESTAMP} instead. Will be removed in 2.0.
     */
    public const TYPE_TIMESTAMP = 'timestamp';
    /**
     * Define the abstract column type as `time`.
     *
     * @deprecated Use {@see ColumnType::TIME} instead. Will be removed in 2.0.
     */
    public const TYPE_TIME = 'time';
    /**
     * Define the abstract column type as `date`.
     *
     * @deprecated Use {@see ColumnType::DATE} instead. Will be removed in 2.0.
     */
    public const TYPE_DATE = 'date';
    /**
     * Define the abstract column type as `binary`.
     *
     * @deprecated Use {@see ColumnType::BINARY} instead. Will be removed in 2.0.
     */
    public const TYPE_BINARY = 'binary';
    /**
     * Define the abstract column type as `boolean`.
     *
     * @deprecated Use {@see ColumnType::BOOLEAN} instead. Will be removed in 2.0.
     */
    public const TYPE_BOOLEAN = 'boolean';
    /**
     * Define the abstract column type as `bit`.
     *
     * @deprecated Use {@see ColumnType::BIT} instead. Will be removed in 2.0.
     */
    public const TYPE_BIT = 'bit';
    /**
     * Define the abstract column type as `money`.
     *
     * @deprecated Use {@see ColumnType::MONEY} instead. Will be removed in 2.0.
     */
    public const TYPE_MONEY = 'money';
    /**
     * Define the abstract column type as `json`.
     *
     * @deprecated Use {@see ColumnType::JSON} instead. Will be removed in 2.0.
     */
    public const TYPE_JSON = 'json';

    /**
     * @return string|null The default schema name.
     */
    public function getDefaultSchema(): string|null;

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
    public function getResultColumn(array $metadata): ColumnInterface|null;

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
     * Returns all unique indexes for the given table.
     *
     * Each array element is of the following structure:
     *
     * ```php
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchemaInterface $table The table metadata.
     *
     * @return string[][] All unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchemaInterface $table): array;

    /**
     * Obtains the metadata for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any.
     * Don't quote the table name.
     * @param bool $refresh Whether to reload the table schema even if it's found in the cache.
     *
     * @return TableSchemaInterface|null Table metadata. `null` if the named table doesn't exist.
     */
    public function getTableSchema(string $name, bool $refresh = false): TableSchemaInterface|null;

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
