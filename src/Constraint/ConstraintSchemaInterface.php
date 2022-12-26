<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * The ConstraintSchemaInterface is an interface that represents a constraint on a database table. A constraint is a
 * rule that is applied to the data in a table to ensure the integrity and correctness of the data.
 *
 * The ConstraintSchemaInterface provides methods for working with table constraints, including methods for getting the
 * name of the constraint, the columns that the constraint is applied to, and the type of constraint.
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
     * @return array The check constraints for all tables in the database. Each array element is an array of the
     * following {@see CheckConstraint} or its child classes.
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
     * @return array The default value constraints for all tables in the database. Each array element is an array of
     * {@see DefaultValueConstraint} or its child classes.
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
     * @return array The unique constraints for all tables in the database. Each array element is an array of the
     * following {@see Constraint} or its child classes.
     */
    public function getSchemaUniques(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the check constraints' information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return array The information metadata for the check constraints of the named table.
     */
    public function getTableChecks(string $name, bool $refresh = false): array;

    /**
     * Obtains the default value constraints information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return array The information metadata for the default value constraints of the named table.
     */
    public function getTableDefaultValues(string $name, bool $refresh = false): array;

    /**
     * Obtains the foreign keys' information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return array The information metadata for the foreign keys of the named table.
     */
    public function getTableForeignKeys(string $name, bool $refresh = false): array;

    /**
     * Obtains the indexes' information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return array The information metadata for the indexes of the named table.
     */
    public function getTableIndexes(string $name, bool $refresh = false): array;

    /**
     * Obtains the primary key for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return Constraint|null The information metadata for the primary key of the named table.
     */
    public function getTablePrimaryKey(string $name, bool $refresh = false): Constraint|null;

    /**
     * Obtains the unique constraints' information for the named table.
     *
     * @param string $name Table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh Whether to reload the information even if it is found in the cache.
     *
     * @return array The information metadata for the unique constraints of the named table.
     */
    public function getTableUniques(string $name, bool $refresh = false): array;
}
