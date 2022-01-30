<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Throwable;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryBuilder;

interface SchemaInterface
{
    public const SCHEMA = 'schema';
    public const PRIMARY_KEY = 'primaryKey';
    public const INDEXES = 'indexes';
    public const CHECKS = 'checks';
    public const FOREIGN_KEYS = 'foreignKeys';
    public const DEFAULT_VALUES = 'defaultValues';
    public const UNIQUES = 'uniques';

    public const TYPE_PK = 'pk';
    public const TYPE_UPK = 'upk';
    public const TYPE_BIGPK = 'bigpk';
    public const TYPE_UBIGPK = 'ubigpk';
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

    public function createQueryBuilder(): QueryBuilder;

    /**
     * @return QueryBuilder the query builder for this connection.
     */
    public function getQueryBuilder(): QueryBuilder;

    public function getDb(): ConnectionInterface;

    public function getDefaultSchema(): ?string;

    public function getSchemaCache(): SchemaCache;

    /**
     * Obtains the metadata for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the table schema even if it is found in the cache.
     *
     * @return TableSchema|null table metadata. `null` if the named table does not exist.
     */
    public function getTableSchema(string $name, bool $refresh = false): ?TableSchema;

    /**
     * Returns the metadata for all tables in the database.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @throws NotSupportedException
     *
     * @return TableSchema[] the metadata for all tables in the database. Each array element is an instance of
     * {@see TableSchema} or its child class.
     */
    public function getTableSchemas(string $schema = '', bool $refresh = false): array;

    /**
     * Returns all schema names in the database, except system schemas.
     *
     * @param bool $refresh whether to fetch the latest available schema names. If this is false, schema names fetched
     * previously (if available) will be returned.
     *
     * @throws NotSupportedException
     *
     * @return string[] all schema names in the database, except system schemas.
     */
    public function getSchemaNames(bool $refresh = false): array;

    /**
     * Returns all table names in the database.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * If not empty, the returned table names will be prefixed with the schema name.
     * @param bool $refresh whether to fetch the latest available table names. If this is false, table names fetched
     * previously (if available) will be returned.
     *
     * @throws NotSupportedException
     *
     * @return string[] all table names in the database.
     */
    public function getTableNames(string $schema = '', bool $refresh = false): array;

    /**
     * Determines the PDO type for the given PHP data value.
     *
     * @param mixed $data the data whose PDO type is to be determined
     *
     * @return int the PDO type
     *
     * {@see http://www.php.net/manual/en/pdo.constants.php}
     */
    public function getPdoType($data): int;

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
     * @param string $name table name.
     */
    public function refreshTableSchema(string $name): void;

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     *
     * @throws InvalidCallException if the DB connection is not active
     *
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     *
     * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
     */
    public function getLastInsertID(string $sequenceName = ''): string;

    /**
     * @return bool whether this DBMS supports [savepoint](http://en.wikipedia.org/wiki/Savepoint).
     */
    public function supportsSavepoint(): bool;

    /**
     * Creates a new savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function createSavepoint(string $name): void;

    /**
     * Releases an existing savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function releaseSavepoint(string $name): void;

    /**
     * Rolls back to a previously created savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function rollBackSavepoint(string $name): void;

    /**
     * Sets the isolation level of the current transaction.
     *
     * @param string $level The transaction isolation level to use for this transaction.
     *
     * This can be one of {@see Transaction::READ_UNCOMMITTED}, {@see Transaction::READ_COMMITTED},
     * {@see Transaction::REPEATABLE_READ} and {@see Transaction::SERIALIZABLE} but also a string containing DBMS
     * specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public function setTransactionIsolationLevel(string $level): void;

    /**
     * Executes the INSERT command, returning primary key values.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column data (name => value) to be inserted into the table.
     *
     * @throws Exception|InvalidCallException|InvalidConfigException|Throwable
     *
     * @return array|false primary key values or false if the command fails.
     */
    public function insert(string $table, array $columns);

    /**
     * Quotes a string value for use in a query.
     *
     * Note that if the parameter is not a string, it will be returned without change.
     *
     * @param int|string $str string to be quoted.
     *
     * @throws Exception
     *
     * @return int|string the properly quoted string.
     *
     * {@see http://www.php.net/manual/en/function.PDO-quote.php}
     */
    public function quoteValue($str);

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name contains schema prefix, the prefix will also be properly quoted. If the table name is already
     * quoted or contains '(' or '{{', then this method will do nothing.
     *
     * @param string $name table name.
     *
     * @return string the properly quoted table name.
     *
     * {@see quoteSimpleTableName()}
     */
    public function quoteTableName(string $name): string;

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name contains prefix, the prefix will also be properly quoted. If the column name is already quoted
     * or contains '(', '[[' or '{{', then this method will do nothing.
     *
     * @param string $name column name.
     *
     * @return string the properly quoted column name.
     *
     * {@see quoteSimpleColumnName()}
     */
    public function quoteColumnName(string $name): string;

    /**
     * Quotes a simple table name for use in a query.
     *
     * A simple table name should contain the table name only without any schema prefix. If the table name is already
     * quoted, this method will do nothing.
     *
     * @param string $name table name.
     *
     * @return string the properly quoted table name.
     */
    public function quoteSimpleTableName(string $name): string;

    /**
     * Quotes a simple column name for use in a query.
     *
     * A simple column name should contain the column name only without any prefix. If the column name is already quoted
     * or is the asterisk character '*', this method will do nothing.
     *
     * @param string $name column name.
     *
     * @return string the properly quoted column name.
     */
    public function quoteSimpleColumnName(string $name): string;

    /**
     * Unquotes a simple table name.
     *
     * A simple table name should contain the table name only without any schema prefix. If the table name is not
     * quoted, this method will do nothing.
     *
     * @param string $name table name.
     *
     * @return string unquoted table name.
     */
    public function unquoteSimpleTableName(string $name): string;

    /**
     * Unquotes a simple column name.
     *
     * A simple column name should contain the column name only without any prefix. If the column name is not quoted or
     * is the asterisk character '*', this method will do nothing.
     *
     * @param string $name column name.
     *
     * @return string unquoted column name.
     */
    public function unquoteSimpleColumnName(string $name): string;

    /**
     * Returns the actual name of a given table name.
     *
     * This method will strip off curly brackets from the given table name and replace the percentage character '%' with
     * {@see ConnectionInterface::tablePrefix}.
     *
     * @param string $name the table name to be converted.
     *
     * @return string the real name of the given table name.
     */
    public function getRawTableName(string $name): string;

    /**
     * Converts a DB exception to a more concrete one if possible.
     *
     * @param \Exception $e
     * @param string $rawSql SQL that produced exception.
     *
     * @return Exception
     */
    public function convertException(\Exception $e, string $rawSql): Exception;

    /**
     * Returns a value indicating whether a SQL statement is for read purpose.
     *
     * @param string $sql the SQL statement.
     *
     * @return bool whether a SQL statement is for read purpose.
     */
    public function isReadQuery(string $sql): bool;

    /**
     * Returns a server version as a string comparable by {@see version_compare()}.
     *
     * @throws Exception
     *
     * @return string server version as a string.
     */
    public function getServerVersion(): string;

    /**
     * Obtains the primary key for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return Constraint|null table primary key, `null` if the table has no primary key.
     */
    public function getTablePrimaryKey(string $name, bool $refresh = false): ?Constraint;

    /**
     * Returns primary keys for all tables in the database.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @throws NotSupportedException
     *
     * @return Constraint[] primary keys for all tables in the database.
     *
     * Each array element is an instance of {@see Constraint} or its child class.
     */
    public function getSchemaPrimaryKeys(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the foreign keys information for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return ForeignKeyConstraint[] table foreign keys.
     */
    public function getTableForeignKeys(string $name, bool $refresh = false): array;

    /**
     * Returns foreign keys for all tables in the database.
     *
     * @param string $schema  the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return ForeignKeyConstraint[][] foreign keys for all tables in the database. Each array element is an array of
     * {@see ForeignKeyConstraint} or its child classes.
     */
    public function getSchemaForeignKeys(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the indexes information for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return IndexConstraint[] table indexes.
     */
    public function getTableIndexes(string $name, bool $refresh = false): array;

    /**
     * Returns indexes for all tables in the database.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return IndexConstraint[][] indexes for all tables in the database. Each array element is an array of
     * {@see IndexConstraint} or its child classes.
     */
    public function getSchemaIndexes(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the unique constraints information for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return Constraint[] table unique constraints.
     */
    public function getTableUniques(string $name, bool $refresh = false): array;

    /**
     * Returns unique constraints for all tables in the database.
     *
     * @param string $schema  the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return Constraint[][] unique constraints for all tables in the database. Each array element is an array of
     * {@see Constraint} or its child classes.
     */
    public function getSchemaUniques(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the check constraints information for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return CheckConstraint[] table check constraints.
     */
    public function getTableChecks(string $name, bool $refresh = false): array;

    /**
     * Returns check constraints for all tables in the database.
     *
     * @param string $schema  the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return CheckConstraint[][] check constraints for all tables in the database. Each array element is an array of
     * {@see CheckConstraint} or its child classes.
     */
    public function getSchemaChecks(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the default value constraints information for the named table.
     *
     * @param string $name  table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return DefaultValueConstraint[] table default value constraints.
     */
    public function getTableDefaultValues(string $name, bool $refresh = false): array;

    /**
     * Returns default value constraints for all tables in the database.
     *
     * @param string $schema  the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return DefaultValueConstraint[] default value constraints for all tables in the database. Each array element is
     * an array of {@see DefaultValueConstraint} or its child classes.
     */
    public function getSchemaDefaultValues(string $schema = '', bool $refresh = false): array;
}
