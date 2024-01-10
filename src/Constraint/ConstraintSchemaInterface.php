<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * Defines the methods to get information about database constraints:
 *
 * - Name of the constraint
 * - Columns that the constraint applies to
 * - Type of constraint
 *
 * A constraint is a rule that's applied to enforce the integrity and correctness of the data.
 */
interface ConstraintSchemaInterface
{
    /**
     * Returns check constraints for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @return array The check constraints for all tables in the database. Each array element is an array of the
     * following {@see CheckConstraint} or its child classes.
     */
    public function getSchemaChecks(string $schema = '', bool $refresh = false): array;

    /**
     * Returns default value constraints for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @return array The default value constraints for all tables in the database. Each array element is an array of
     * {@see DefaultValueConstraint} or its child classes.
     */
    public function getSchemaDefaultValues(string $schema = '', bool $refresh = false): array;

    /**
     * Returns foreign keys for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @return array The foreign keys for all tables in the database. Each array element is an array of the following
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
     * @return array The indexes for all tables in the database. Each array element is an array of the following
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
     * @return array The primary keys for all tables in the database. Each array element is an instance of
     * {@see Constraint} or its child class.
     *
     * @psalm-return list<Constraint>
     */
    public function getSchemaPrimaryKeys(string $schema = '', bool $refresh = false): array;

    /**
     * Returns unique constraints for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @return array The unique constraints for all tables in the database. Each array element is an array of the
     * following {@see Constraint} or its child classes.
     */
    public function getSchemaUniques(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the check constraints' information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return array The information metadata for the check constraints of the named table.
     */
    public function getTableChecks(string $name, bool $refresh = false): array;

    /**
     * Obtains the default value constraints information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return array The information metadata for the default value constraints of the named table.
     */
    public function getTableDefaultValues(string $name, bool $refresh = false): array;

    /**
     * Obtains the foreign keys' information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return array The information metadata for the foreign keys of the named table.
     */
    public function getTableForeignKeys(string $name, bool $refresh = false): array;

    /**
     * Obtains the indexes' information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return IndexConstraint[] The information metadata for the indexes of the named table.
     */
    public function getTableIndexes(string $name, bool $refresh = false): array;

    /**
     * Obtains the primary key for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return Constraint|null The information metadata for the primary key of the named table.
     */
    public function getTablePrimaryKey(string $name, bool $refresh = false): Constraint|null;

    /**
     * Obtains the unique constraints' information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return array The information metadata for the unique constraints of the named table.
     */
    public function getTableUniques(string $name, bool $refresh = false): array;
}
