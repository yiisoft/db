<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraint;

/**
 * ConstraintFinderTrait provides methods for getting a table constraint information.
 *
 * @property CheckConstraint[] $schemaChecks Check constraints for all tables in the database. Each array element is an
 * array of {@see CheckConstraint} or its child classes. This property is read-only.
 * @property DefaultValueConstraint[] $schemaDefaultValues Default value constraints for all tables in the database.
 * Each array element is an array of {@see DefaultValueConstraint} or its child classes. This property is read-only.
 * @property ForeignKeyConstraint[] $schemaForeignKeys Foreign keys for all tables in the database. Each array element
 * is an array of {@see ForeignKeyConstraint} or its child classes. This property is read-only.
 * @property IndexConstraint[] $schemaIndexes Indexes for all tables in the database. Each array element is an array of
 * {@see IndexConstraint} or its child classes. This property is read-only.
 * @property Constraint[] $schemaPrimaryKeys Primary keys for all tables in the database. Each array element is an
 * instance of {@see Constraint} or its child class. This property is read-only.
 * @property IndexConstraint[] $schemaUniques Unique constraints for all tables in the database. Each array element is
 * an array of {@see IndexConstraint} or its child classes. This property is read-only.
 */
trait ConstraintFinderTrait
{
    /**
     * Returns the metadata of the given type for the given table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param string $type metadata type.
     * @param bool $refresh whether to reload the table metadata even if it is found in the cache.
     *
     * @return mixed metadata.
     */
    abstract protected function getTableMetadata(string $name, string $type, bool $refresh);

    /**
     * Returns the metadata of the given type for all tables in the given schema.
     *
     * @param string $schema the schema of the metadata. Defaults to empty string, meaning the current or default schema
     * name.
     * @param string $type metadata type.
     * @param bool $refresh whether to fetch the latest available table metadata. If this is `false`, cached data may be
     * returned if available.
     *
     * @return array array of metadata.
     */
    abstract protected function getSchemaMetadata(string $schema, string $type, bool $refresh): array;

    /**
     * Loads a primary key for the given table.
     *
     * @param string $tableName table name.
     *
     * @return Constraint|null primary key for the given table, `null` if the table has no primary key.
     */
    abstract protected function loadTablePrimaryKey(string $tableName): ?Constraint;

    /**
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName table name.
     *
     * @return array foreign keys for the given table.
     */
    abstract protected function loadTableForeignKeys(string $tableName): array;

    /**
     * Loads all indexes for the given table.
     *
     * @param string $tableName table name.
     *
     * @return array indexes for the given table.
     */
    abstract protected function loadTableIndexes(string $tableName): array;

    /**
     * Loads all unique constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @return array unique constraints for the given table.
     */
    abstract protected function loadTableUniques(string $tableName): array;

    /**
     * Loads all check constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @return array check constraints for the given table.
     */
    abstract protected function loadTableChecks(string $tableName): array;

    /**
     * Loads all default value constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @return array default value constraints for the given table.
     */
    abstract protected function loadTableDefaultValues(string $tableName): array;

    /**
     * Obtains the primary key for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return Constraint|null table primary key, `null` if the table has no primary key.
     */
    public function getTablePrimaryKey(string $name, bool $refresh = false): ?Constraint
    {
        return $this->getTableMetadata($name, 'primaryKey', $refresh);
    }

    /**
     * Returns primary keys for all tables in the database.
     *
     * @param string $schema  the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is `false`, cached data may be
     * returned if available.
     *
     * @return array primary keys for all tables in the database. Each array element is an instance of
     * {@see Constraint} or its child class.
     */
    public function getSchemaPrimaryKeys(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'primaryKey', $refresh);
    }

    /**
     * Obtains the foreign keys information for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return array table foreign keys.
     */
    public function getTableForeignKeys(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'foreignKeys', $refresh);
    }

    /**
     * Returns foreign keys for all tables in the database.
     *
     * @param string $schema  the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return array foreign keys for all tables in the database. Each array element is an array of
     * {@see ForeignKeyConstraint} or its child classes.
     */
    public function getSchemaForeignKeys(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'foreignKeys', $refresh);
    }

    /**
     * Obtains the indexes information for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return array table indexes.
     */
    public function getTableIndexes(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'indexes', $refresh);
    }

    /**
     * Returns indexes for all tables in the database.
     *
     * @param string $schema  the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return array indexes for all tables in the database. Each array element is an array of
     * {@see IndexConstraint} or its child classes.
     */
    public function getSchemaIndexes(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'indexes', $refresh);
    }

    /**
     * Obtains the unique constraints information for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return array table unique constraints.
     */
    public function getTableUniques(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'uniques', $refresh);
    }

    /**
     * Returns unique constraints for all tables in the database.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return array unique constraints for all tables in the database. Each array element is an array of
     * {@see Constraint} or its child classes.
     */
    public function getSchemaUniques(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'uniques', $refresh);
    }

    /**
     * Obtains the check constraints information for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return array table check constraints.
     */
    public function getTableChecks(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'checks', $refresh);
    }

    /**
     * Returns check constraints for all tables in the database.
     *
     * @param string $schema  the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false, cached data may be
     * returned if available.
     *
     * @return array check constraints for all tables in the database. Each array element is an array of
     * {@see CheckConstraint} or its child classes.
     */
    public function getSchemaChecks(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'checks', $refresh);
    }

    /**
     * Obtains the default value constraints information for the named table.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param bool $refresh whether to reload the information even if it is found in the cache.
     *
     * @return array table default value constraints.
     */
    public function getTableDefaultValues(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'defaultValues', $refresh);
    }

    /**
     * Returns default value constraints for all tables in the database.
     *
     * @param string $schema  the schema of the tables. Defaults to empty string, meaning the current or default schema
     * name.
     * @param bool $refresh whether to fetch the latest available table schemas. If this is false,
     * cached data may be returned if available.
     *
     * @return array default value constraints for all tables in the database. Each array element is
     * an array of {@see DefaultValueConstraint} or its child classes.
     */
    public function getSchemaDefaultValues(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'defaultValues', $refresh);
    }
}
