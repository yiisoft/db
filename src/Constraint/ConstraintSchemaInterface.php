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
     * Returns foreign keys for all tables in the database.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh Whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @return ForeignKey[][] The foreign keys for all tables in the database, indexed by the name of the table the
     * foreign keys belong to.
     *
     * @psalm-return array<string, ForeignKey[]>
     */
    public function getSchemaForeignKeys(string $schema = '', bool $refresh = false): array;

    /**
     * Obtains the check constraints' information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return Check[] The information metadata for the check constraints of the named table.
     */
    public function getTableChecks(string $name, bool $refresh = false): array;

    /**
     * Obtains the default value constraints information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return DefaultValue[] The information metadata for the default value constraints of the named table.
     */
    public function getTableDefaultValues(string $name, bool $refresh = false): array;

    /**
     * Obtains the foreign keys' information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return ForeignKey[] The information metadata for the foreign keys of the named table.
     */
    public function getTableForeignKeys(string $name, bool $refresh = false): array;

    /**
     * Obtains the indexes' information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return Index[] The information metadata for the indexes of the named table.
     */
    public function getTableIndexes(string $name, bool $refresh = false): array;

    /**
     * Obtains the primary key for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return Index|null The information metadata for the primary key of the named table.
     */
    public function getTablePrimaryKey(string $name, bool $refresh = false): ?Index;

    /**
     * Obtains the unique constraints' information for the named table.
     *
     * @param string $name Table name. The table name may contain a schema name if any. Don't quote the table name.
     * @param bool $refresh Whether to reload the information, even if it's found in the cache.
     *
     * @return Index[] The information metadata for the unique constraints of the named table.
     */
    public function getTableUniques(string $name, bool $refresh = false): array;
}
