<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Throwable;
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
    public const SCHEMA = 'schema';
    public const PRIMARY_KEY = 'primaryKey';
    public const INDEXES = 'indexes';
    public const CHECKS = 'checks';
    public const FOREIGN_KEYS = 'foreignKeys';
    public const DEFAULT_VALUES = 'defaultValues';
    public const UNIQUES = 'uniques';
    public const DEFAULTS = 'defaults';
    /**
     * Types of supported indexes {@see QueryBuilderInterface::createIndex()}.
     * MySQL, MSSQL, Oracle, PostgreSQL, SQLite
     */
    public const INDEX_UNIQUE = 'UNIQUE';
    /* MySQL, PostgreSQL */
    public const INDEX_BTREE = 'BTREE';
    public const INDEX_HASH = 'HASH';
    /* MySQL */
    public const INDEX_FULLTEXT = 'FULLTEXT';
    public const INDEX_SPATIAL = 'SPATIAL';
    /* PostgreSQL */
    public const INDEX_GIST = 'GIST';
    public const INDEX_GIN = 'GIN';
    public const INDEX_BRIN = 'BRIN';
    /* MS SQL */
    public const INDEX_CLUSTERED = 'CLUSTERED';
    public const INDEX_NONCLUSTERED = 'NONCLUSTERED';
    /* Oracle */
    public const INDEX_BITMAP = 'BITMAP';
    /* DB Types */
    public const TYPE_PK = 'pk';
    public const TYPE_UPK = 'upk';
    public const TYPE_BIGPK = 'bigpk';
    public const TYPE_UBIGPK = 'ubigpk';
    public const TYPE_UUID_PK = 'uuid_pk';
    public const TYPE_UUID_PK_SEQ = 'uuid_pk_seq';
    public const TYPE_UUID = 'uuid';
    public const TYPE_CHAR = 'char';
    public const TYPE_STRING = 'string';
    public const TYPE_TEXT = 'text';
    public const TYPE_TINYINT = 'tinyint';
    public const TYPE_SMALLINT = 'smallint';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BIGINT = 'bigint';
    public const TYPE_FLOAT = 'float';
    public const TYPE_DOUBLE = 'double';
    public const TYPE_DECIMAL = 'decimal';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_TIMESTAMP = 'timestamp';
    public const TYPE_TIME = 'time';
    public const TYPE_DATE = 'date';
    public const TYPE_BINARY = 'binary';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_MONEY = 'money';
    public const TYPE_JSON = 'json';
    public const TYPE_JSONB = 'jsonb';
    /* PHP Types */
    public const PHP_TYPE_INTEGER = 'integer';
    public const PHP_TYPE_STRING = 'string';
    public const PHP_TYPE_BOOLEAN = 'boolean';
    public const PHP_TYPE_DOUBLE = 'double';
    public const PHP_TYPE_RESOURCE = 'resource';
    public const PHP_TYPE_ARRAY = 'array';
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
     * Determines the PDO type for the given PHP data value.
     *
     * @param mixed $data The data to find PDO type for.
     *
     * @return int The PDO type.
     *
     * @link https://www.php.net/manual/en/pdo.constants.php
     */
    public function getPdoType(mixed $data): int;

    /**
     * Returns the actual name of a given table name.
     *
     * This method will strip off curly brackets from the given table name and replace the percentage character '%' with
     * {@see ConnectionInterface::tablePrefix}.
     *
     * @param string $name The table name to convert.
     *
     * @return string The real name of the given table name.
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
     * @throws NotSupportedException
     *
     * @return array The metadata for all tables in the database. Each array element is an instance of
     * {@see TableSchemaInterface} or its child class.
     */
    public function getTableSchemas(string $schema = '', bool $refresh = false): array;

    /**
     * Returns a value indicating whether an SQL statement is for read purpose.
     *
     * @param string $sql The SQL statement.
     *
     * @return bool Whether an SQL statement is for read purpose.
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
     * Enable and disable the schema cache.
     *
     * @param bool $value Whether to enable or disable the schema cache.
     */
    public function schemaCacheEnable(bool $value): void;

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
