<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Throwable;
use Yiisoft\Db\Command\DataType;
use Yiisoft\Db\Constraint\ConstraintSchemaInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\Builder\ColumnInterface;

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
     */
    public const INDEX_UNIQUE = 'UNIQUE';
    /**
     * Define the type of the index as `BTREE`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MySQL`, `PostgreSQL`.
     */
    public const INDEX_BTREE = 'BTREE';
    /**
     * Define the type of the index as `HASH`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MySQL`, `PostgreSQL`.
     */
    public const INDEX_HASH = 'HASH';
    /**
     * Define the type of the index as `FULLTEXT`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MySQL`.
     */
    public const INDEX_FULLTEXT = 'FULLTEXT';
    /**
     * Define the type of the index as `SPATIAL`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MySQL`.
     */
    public const INDEX_SPATIAL = 'SPATIAL';
    /**
     * Define the type of the index as `GIST`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `PostgreSQL`.
     */
    public const INDEX_GIST = 'GIST';
    /**
     * Define the type of the index as `GIN`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `PostgreSQL`.
     */
    public const INDEX_GIN = 'GIN';
    /**
     * Define the type of the index as `BRIN`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `PostgreSQL`.
     */
    public const INDEX_BRIN = 'BRIN';
    /**
     * Define the type of the index as `CLUSTERED`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MSSQL`.
     */
    public const INDEX_CLUSTERED = 'CLUSTERED';
    /**
     * Define the type of the index as `NONCLUSTERED`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `MSSQL`.
     */
    public const INDEX_NONCLUSTERED = 'NONCLUSTERED';
    /**
     * Define the type of the index as `BITMAP`, it's used in {@see DDLQueryBuilderInterface::createIndex()}.
     *
     * Supported by `Oracle`.
     */
    public const INDEX_BITMAP = 'BITMAP';
    /**
     * Define the abstract column type as a primary key.
     */
    public const TYPE_PK = 'pk';
    /**
     * Define the abstract column type as an `unsigned` primary key.
     */
    public const TYPE_UPK = 'upk';
    /**
     * Define the abstract column type as big primary key.
     */
    public const TYPE_BIGPK = 'bigpk';
    /**
     * Define the abstract column type as `unsigned` big primary key.
     */
    public const TYPE_UBIGPK = 'ubigpk';
    /**
     * Define the abstract column type as an `uuid` primary key.
     */
    public const TYPE_UUID_PK = 'uuid_pk';
    /**
     * Define the abstract column type as an`uuid` primary key with a sequence.
     */
    public const TYPE_UUID_PK_SEQ = 'uuid_pk_seq';
    /**
     * Define the abstract column type as `uuid`.
     */
    public const TYPE_UUID = 'uuid';
    /**
     * Define the abstract column type as `char`.
     */
    public const TYPE_CHAR = 'char';
    /**
     * Define the abstract column type as `string`.
     */
    public const TYPE_STRING = 'string';
    /**
     * Define the abstract column type as `text`.
     */
    public const TYPE_TEXT = 'text';
    /**
     * Define the abstract column type as `tinyint`.
     */
    public const TYPE_TINYINT = 'tinyint';
    /**
     * Define the abstract column type as `smallint`.
     */
    public const TYPE_SMALLINT = 'smallint';
    /**
     * Define the abstract column type as `integer`.
     */
    public const TYPE_INTEGER = 'integer';
    /**
     * Define the abstract column type as `bigint`.
     */
    public const TYPE_BIGINT = 'bigint';
    /**
     * Define the abstract column type as `float`.
     */
    public const TYPE_FLOAT = 'float';
    /**
     * Define the abstract column type as `double`.
     */
    public const TYPE_DOUBLE = 'double';
    /**
     * Define the abstract column type as `decimal`.
     */
    public const TYPE_DECIMAL = 'decimal';
    /**
     * Define the abstract column type as `datetime`.
     */
    public const TYPE_DATETIME = 'datetime';
    /**
     * Define the abstract column type as `timestamp`.
     */
    public const TYPE_TIMESTAMP = 'timestamp';
    /**
     * Define the abstract column type as `time`.
     */
    public const TYPE_TIME = 'time';
    /**
     * Define the abstract column type as `date`.
     */
    public const TYPE_DATE = 'date';
    /**
     * Define the abstract column type as `binary`.
     */
    public const TYPE_BINARY = 'binary';
    /**
     * Define the abstract column type as `boolean`.
     */
    public const TYPE_BOOLEAN = 'boolean';
    /**
     * Define the abstract column type as `money`.
     */
    public const TYPE_MONEY = 'money';
    /**
     * Define the abstract column type as `json`.
     */
    public const TYPE_JSON = 'json';
    /**
     * Define the abstract column type as `jsonb`.
     *
     * @deprecated will be removed in version 2.0.0. Use `SchemaInterface::TYPE_JSON` instead.
     */
    public const TYPE_JSONB = 'jsonb';

    /**
     * Define the php type as `integer` for cast to php value.
     */
    public const PHP_TYPE_INTEGER = 'integer';
    /**
     * Define the php type as `string` for cast to php value.
     */
    public const PHP_TYPE_STRING = 'string';
    /**
     * Define the php type as `boolean` for cast to php value.
     */
    public const PHP_TYPE_BOOLEAN = 'boolean';
    /**
     * Define the php type as `double` for cast to php value.
     */
    public const PHP_TYPE_DOUBLE = 'double';
    /**
     * Define the php type as `resource` for cast to php value.
     */
    public const PHP_TYPE_RESOURCE = 'resource';
    /**
     * Define the php type as `array` for cast to php value.
     */
    public const PHP_TYPE_ARRAY = 'array';
    /**
     * Define the php type as `null` for cast to php value.
     */
    public const PHP_TYPE_NULL = 'NULL';

    /**
     * @psalm-param string[]|int[]|int|string|null $length
     */
    public function createColumn(string $type, array|int|string $length = null): ColumnInterface;

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
     * @see DataType
     */
    public function getDataType(mixed $data): int;

    /**
     * Returns the actual name of a given table name.
     *
     * This method will strip off curly brackets from the given table name and replace the percentage character '%' with
     * {@see ConnectionInterface::tablePrefix}.
     *
     * @param string $name The table name to convert.
     *
     * @return string The real name of the given table name.
     *
     * @deprecated Use {@see Quoter::getRawTableName()}. Will be removed in version 2.0.0.
     */
    public function getRawTableName(string $name): string;

    /**
     * Returns all schema names in the database, except system schemas.
     *
     * @param bool $refresh Whether to fetch the latest available schema names. If this is `false`, schema names fetched
     * before (if available) will be returned.
     *
     * @throws NotSupportedException
     *
     * @return array All schemas name in the database, except system schemas.
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
     * @throws NotSupportedException
     *
     * @return array All tables name in the database.
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array All unique indexes for the given table.
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
     * @return array The metadata for all tables in the database.
     *
     * @psalm-return list<TableSchemaInterface>
     */
    public function getTableSchemas(string $schema = '', bool $refresh = false): array;

    /**
     * Returns a value indicating whether an SQL statement is for read purpose.
     *
     * @param string $sql The SQL statement.
     *
     * @return bool Whether an SQL statement is for read purpose.
     *
     * @deprecated Use {@see DbStringHelper::isReadQuery()}. Will be removed in version 2.0.0.
     */
    public function isReadQuery(string $sql): bool;

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
     * @return array All view names in the database.
     */
    public function getViewNames(string $schema = '', bool $refresh = false): array;
}
