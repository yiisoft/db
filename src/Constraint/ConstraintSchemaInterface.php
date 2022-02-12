<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * ConstraintSchemaInterface defines methods for getting a table constraint information.
 */
interface ConstraintSchemaInterface
{
    /**
     * Returns check constraints for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return CheckConstraint[][] Check constraints for all tables in the database. Each array element is an array of
     * {@see CheckConstraint} or its child classes.
     */
    public function getSchemaChecks(string $schema = '', bool $refresh = false): array;

    /**
     * Returns default value constraints for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return DefaultValueConstraint[] Default value constraints for all tables in the database. Each array element is
     * an array of {@see DefaultValueConstraint} or its child classes.
     */
    public function getSchemaDefaultValues(string $schema = '', bool $refresh = false): array;

    /**
     * Returns foreign keys for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return ForeignKeyConstraint[][] Foreign keys for all tables in the database. Each array element is an array of
     * {@see ForeignKeyConstraint} or its child classes.
     */
    public function getSchemaForeignKeys(string $schema = '', bool $refresh = false): array;

    /**
     * Returns indexes for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return IndexConstraint[][] Indexes for all tables in the database. Each array element is an array of
     * {@see IndexConstraint} or its child classes.
     */
    public function getSchemaIndexes(string $schema = '', bool $refresh = false): array;

    /**
     * Returns primary keys for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @return Constraint[] Primary keys for all tables in the database.
     *
     * Each array element is an instance of {@see Constraint} or its child class.
     */
    public function getSchemaPrimaryKeys(string $schema = '', bool $refresh = false): array;

    /**
     * Returns unique constraints for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return Constraint[][] Unique constraints for all tables in the database. Each array element is an array of
     * {@see Constraint} or its child classes.
     */
    public function getSchemaUniques(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the check constraints' information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return CheckConstraint[] Table check constraints.
     */
    public function getTableChecks(string $name, bool $refresh = false): array;

    /**
     * Obtains the default value constraints information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return DefaultValueConstraint[] Table default value constraints.
     */
    public function getTableDefaultValues(string $name, bool $refresh = false): array;

    /**
     * Obtains the foreign keys' information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return ForeignKeyConstraint[] Table foreign keys.
     */
    public function getTableForeignKeys(string $name, bool $refresh = false): array;

    /**
     * Obtains the indexes' information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return IndexConstraint[] Table indexes.
     */
    public function getTableIndexes(string $name, bool $refresh = false): array;

    /**
     * Obtains the primary key for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return Constraint|null Table primary key, `null` if the table has no primary key.
     */
    public function getTablePrimaryKey(string $name, bool $refresh = false): ?Constraint;

    /**
     * Obtains the unique constraints' information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return Constraint[] Table unique constraints.
     */
    public function getTableUniques(string $name, bool $refresh = false): array;
}
