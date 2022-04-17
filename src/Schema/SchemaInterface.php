<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Constraint\ConstraintSchemaInterface;
use Yiisoft\Db\Exception\NotSupportedException;

interface SchemaInterface extends ConstraintSchemaInterface
{
    /**
     * Return default schema name.
     */
    public function getDefaultSchema(): ?string;

    /**
     * @inheritDoc
     */
    public function getLastInsertID(?string $sequenceName = null): string;

    /**
     * Determines the PDO type for the given PHP data value.
     *
     * @param mixed $data The data whose PDO type is to be determined
     *
     * @return int The PDO type
     *
     * @link http://www.php.net/manual/en/pdo.constants.php
     */
    public function getPdoType(mixed $data): int;

    /**
     * Returns the actual name of a given table name.
     *
     * This method will strip off curly brackets from the given table name and replace the percentage character '%' with
     * {@see ConnectionInterface::tablePrefix}.
     *
     * @param string $name The table name to be converted.
     *
     * @return string The real name of the given table name.
     */
    public function getRawTableName(string $name): string;

    /**
     * Return schema cache instance.
     */
    public function getSchemaCache(): SchemaCache;

    /**
     * Returns all schema names in the database, except system schemas.
     *
     * @param bool $refresh Whether to fetch the latest available schema names. If this is false, schema names fetched
     * previously (if available) will be returned.
     *
     * @throws NotSupportedException
     *
     * @return array All schema names in the database, except system schemas.
     */
    public function getSchemaNames(bool $refresh = false): array;

    /**
     * Returns all table names in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * If not empty, the returned table names will be prefixed with the schema name.
     * @param bool $refresh Whether to fetch the latest available table names. If this is false, table names fetched
     * previously (if available) will be returned.
     *
     * @throws NotSupportedException
     *
     * @return array All table names in the database.
     */
    public function getTableNames(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the metadata for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the table schema even if it is found in the cache.
     *
     * @return TableSchema|null Table metadata. `null` if the named table does not exist.
     */
    public function getTableSchema(string $name, bool $refresh = false): ?TableSchema;

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
     * {@see TableSchema} or its child class.
     */
    public function getTableSchemas(string $schema = '', bool $refresh = false): array;

    /**
     * Returns a value indicating whether a SQL statement is for read purpose.
     *
     * @param string $sql The SQL statement.
     *
     * @return bool Whether a SQL statement is for read purpose.
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
     * @return bool whether this DBMS supports [savepoint](http://en.wikipedia.org/wiki/Savepoint).
     */
    public function supportsSavepoint(): bool;
}
