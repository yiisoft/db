<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Psr\SimpleCache\InvalidArgumentException;
use Throwable;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_key_exists;
use function gettype;
use function is_array;

/**
 * Provides a set of methods for working with database schemas such as creating, modifying, and inspecting tables,
 * columns, and other database objects.
 *
 * It's a powerful and flexible tool that allows you to perform a wide range of database operations in a
 * database-agnostic way.
 */
abstract class AbstractSchema implements SchemaInterface
{
    /**
     * Schema cache version, to detect incompatibilities in cached values when the data format of the cache changes.
     */
    protected const SCHEMA_CACHE_VERSION = 1;
    protected const CACHE_VERSION = 'cacheVersion';
    /**
     * @var string|null $defaultSchema The default schema name used for the current session.
     */
    protected string|null $defaultSchema = null;
    /**
     * @var (ColumnInterface|null)[] Saved columns from query results.
     * @psalm-var array<string, ColumnInterface|null>
     */
    protected array $resultColumns = [];
    protected array $viewNames = [];
    private array $schemaNames = [];
    /** @psalm-var string[]|array */
    private array $tableNames = [];
    private array $tableMetadata = [];

    public function __construct(protected ConnectionInterface $db, private SchemaCache $schemaCache)
    {
    }

    /**
     * @param string $name The table name.
     *
     * @return array The cache key for the specified table name.
     */
    abstract protected function getCacheKey(string $name): array;

    /**
     * @return string The cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     */
    abstract protected function getCacheTag(): string;

    /**
     * Returns the cache key for the column metadata received from the query result.
     *
     * @param array $metadata The column metadata from the query result.
     */
    abstract protected function getResultColumnCacheKey(array $metadata): string;

    /**
     * Creates a new column instance according to the column metadata received from the query result.
     *
     * @param array $metadata The column metadata from the query result.
     */
    abstract protected function loadResultColumn(array $metadata): ColumnInterface|null;

    /**
     * Loads all check constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return array The check constraints for the given table.
     */
    abstract protected function loadTableChecks(string $tableName): array;

    /**
     * Loads all default value constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return array The default value constraints for the given table.
     */
    abstract protected function loadTableDefaultValues(string $tableName): array;

    /**
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return array The foreign keys for the given table.
     */
    abstract protected function loadTableForeignKeys(string $tableName): array;

    /**
     * Loads all indexes for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return IndexConstraint[] The indexes for the given table.
     */
    abstract protected function loadTableIndexes(string $tableName): array;

    /**
     * Loads a primary key for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return Constraint|null The primary key for the given table. `null` if the table has no primary key.
     */
    abstract protected function loadTablePrimaryKey(string $tableName): Constraint|null;

    /**
     * Loads all unique constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return array The unique constraints for the given table.
     */
    abstract protected function loadTableUniques(string $tableName): array;

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name The table name.
     *
     * @return TableSchemaInterface|null DBMS-dependent table metadata, `null` if the table doesn't exist.
     */
    abstract protected function loadTableSchema(string $name): TableSchemaInterface|null;

    public function getDefaultSchema(): string|null
    {
        return $this->defaultSchema;
    }

    public function getDataType(mixed $data): int
    {
        return match (gettype($data)) {
            // php type => SQL data type
            GettypeResult::BOOLEAN => DataType::BOOLEAN,
            GettypeResult::INTEGER => DataType::INTEGER,
            GettypeResult::RESOURCE => DataType::LOB,
            GettypeResult::NULL => DataType::NULL,
            default => DataType::STRING,
        };
    }

    final public function getResultColumn(array $metadata): ColumnInterface|null
    {
        if (empty($metadata)) {
            return null;
        }

        $cacheKey = $this->getResultColumnCacheKey($metadata);

        if (array_key_exists($cacheKey, $this->resultColumns)) {
            return $this->resultColumns[$cacheKey];
        }

        $isCacheEnabled = $this->schemaCache->isEnabled();

        if ($isCacheEnabled) {
            /** @var ColumnInterface */
            $this->resultColumns[$cacheKey] = $this->schemaCache->get($cacheKey);

            if (isset($this->resultColumns[$cacheKey])) {
                return $this->resultColumns[$cacheKey];
            }
        }

        $column = $this->loadResultColumn($metadata);
        $this->resultColumns[$cacheKey] = $column;

        if ($column !== null && $isCacheEnabled) {
            $this->schemaCache->set($cacheKey, $column, $this->getCacheTag());
        }

        return $column;
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function getSchemaChecks(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::CHECKS, $refresh);
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function getSchemaDefaultValues(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::DEFAULT_VALUES, $refresh);
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function getSchemaForeignKeys(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::FOREIGN_KEYS, $refresh);
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function getSchemaIndexes(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::INDEXES, $refresh);
    }

    /**
     * @throws NotSupportedException If this method isn't supported by the underlying DBMS.
     */
    public function getSchemaNames(bool $refresh = false): array
    {
        if (empty($this->schemaNames) || $refresh) {
            $this->schemaNames = $this->findSchemaNames();
        }

        return $this->schemaNames;
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    public function getSchemaPrimaryKeys(string $schema = '', bool $refresh = false): array
    {
        /** @psalm-var list<Constraint> */
        return $this->getSchemaMetadata($schema, SchemaInterface::PRIMARY_KEY, $refresh);
    }

    /**
     * @throws NotSupportedException
     * @throws InvalidArgumentException
     */
    public function getSchemaUniques(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::UNIQUES, $refresh);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getTableChecks(string $name, bool $refresh = false): array
    {
        /** @psalm-var mixed $tableChecks */
        $tableChecks = $this->getTableMetadata($name, SchemaInterface::CHECKS, $refresh);
        return is_array($tableChecks) ? $tableChecks : [];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getTableDefaultValues(string $name, bool $refresh = false): array
    {
        /** @psalm-var mixed $tableDefaultValues */
        $tableDefaultValues = $this->getTableMetadata($name, SchemaInterface::DEFAULT_VALUES, $refresh);
        return is_array($tableDefaultValues) ? $tableDefaultValues : [];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getTableForeignKeys(string $name, bool $refresh = false): array
    {
        /** @psalm-var mixed $tableForeignKeys */
        $tableForeignKeys = $this->getTableMetadata($name, SchemaInterface::FOREIGN_KEYS, $refresh);
        return is_array($tableForeignKeys) ? $tableForeignKeys : [];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getTableIndexes(string $name, bool $refresh = false): array
    {
        /** @var IndexConstraint[] */
        return $this->getTableMetadata($name, SchemaInterface::INDEXES, $refresh);
    }

    /**
     * @throws NotSupportedException If this method isn't supported by the underlying DBMS.
     */
    public function getTableNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->tableNames[$schema]) || $refresh) {
            $this->tableNames[$schema] = $this->findTableNames($schema);
        }

        return is_array($this->tableNames[$schema]) ? $this->tableNames[$schema] : [];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getTablePrimaryKey(string $name, bool $refresh = false): Constraint|null
    {
        /** @psalm-var mixed $tablePrimaryKey */
        $tablePrimaryKey = $this->getTableMetadata($name, SchemaInterface::PRIMARY_KEY, $refresh);
        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getTableSchema(string $name, bool $refresh = false): TableSchemaInterface|null
    {
        /** @psalm-var mixed $tableSchema */
        $tableSchema = $this->getTableMetadata($name, SchemaInterface::SCHEMA, $refresh);
        return $tableSchema instanceof TableSchemaInterface ? $tableSchema : null;
    }

    /**
     * @throws NotSupportedException
     * @throws InvalidArgumentException
     */
    public function getTableSchemas(string $schema = '', bool $refresh = false): array
    {
        /** @psalm-var list<TableSchemaInterface> */
        return $this->getSchemaMetadata($schema, SchemaInterface::SCHEMA, $refresh);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array The metadata for table unique constraints.
     */
    public function getTableUniques(string $name, bool $refresh = false): array
    {
        /** @psalm-var mixed $tableUniques */
        $tableUniques = $this->getTableMetadata($name, SchemaInterface::UNIQUES, $refresh);
        return is_array($tableUniques) ? $tableUniques : [];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function refresh(): void
    {
        if ($this->schemaCache->isEnabled()) {
            $this->schemaCache->invalidate($this->getCacheTag());
        }

        $this->tableNames = [];
        $this->tableMetadata = [];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function refreshTableSchema(string $name): void
    {
        $rawName = $this->db->getQuoter()->getRawTableName($name);

        unset($this->tableMetadata[$rawName]);

        $this->tableNames = [];

        if ($this->schemaCache->isEnabled()) {
            $this->schemaCache->remove($this->getCacheKey($rawName));
        }
    }

    public function enableCache(bool $value): void
    {
        $this->schemaCache->setEnabled($value);
    }

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     *
     * This method should be overridden by child classes to support this feature because the default
     * implementation simply throws an exception.
     *
     * @throws NotSupportedException If the DBMS doesn't support this method.
     *
     * @return array All schemas name in the database, except system schemas.
     */
    protected function findSchemaNames(): array
    {
        throw new NotSupportedException(static::class . ' does not support fetching all schema names.');
    }

    /**
     * Returns all table names in the database.
     *
     * This method should be overridden by child classes to support this feature because the default
     * implementation simply throws an exception.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema.
     *
     * @throws NotSupportedException If the DBMS doesn't support this method.
     *
     * @return array All tables name in the database. The names have NO schema name prefix.
     */
    protected function findTableNames(string $schema): array
    {
        throw new NotSupportedException(static::class . ' does not support fetching all table names.');
    }

    /**
     * Returns the metadata of the given type for all tables in the given schema.
     *
     * @param string $schema The schema of the metadata. Defaults to empty string, meaning the current or default schema
     * name.
     * @param string $type The metadata type.
     * @param bool $refresh Whether to fetch the latest available table metadata. If this is `false`, cached data may be
     * returned if available.
     *
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @return array The metadata of the given type for all tables in the given schema.
     *
     * @psalm-return list<Constraint|TableSchemaInterface|array>
     */
    protected function getSchemaMetadata(string $schema, string $type, bool $refresh): array
    {
        $metadata = [];
        /** @psalm-var string[] $tableNames */
        $tableNames = $this->getTableNames($schema, $refresh);

        foreach ($tableNames as $name) {
            $name = $this->db->getQuoter()->quoteSimpleTableName($name);

            if ($schema !== '') {
                $name = $schema . '.' . $name;
            }

            $tableMetadata = $this->getTableTypeMetadata($type, $name, $refresh);

            if ($tableMetadata !== null) {
                $metadata[] = $tableMetadata;
            }
        }

        return $metadata;
    }

    /**
     * Returns the metadata of the given type for the given table.
     *
     * @param string $name The table name. The table name may contain a schema name if any.
     * Don't quote the table name.
     * @param string $type The metadata type.
     * @param bool $refresh whether to reload the table metadata even if it's found in the cache.
     *
     * @throws InvalidArgumentException
     *
     * @return mixed The metadata of the given type for the given table.
     */
    protected function getTableMetadata(string $name, string $type, bool $refresh = false): mixed
    {
        $rawName = $this->db->getQuoter()->getRawTableName($name);

        if (!isset($this->tableMetadata[$rawName])) {
            $this->loadTableMetadataFromCache($rawName);
        }

        if ($refresh || !isset($this->tableMetadata[$rawName][$type])) {
            /** @psalm-suppress MixedArrayAssignment */
            $this->tableMetadata[$rawName][$type] = $this->loadTableTypeMetadata($type, $rawName);
            $this->saveTableMetadataToCache($rawName);
        }

        /** @psalm-suppress MixedArrayAccess */
        return $this->tableMetadata[$rawName][$type];
    }

    /**
     * This method returns the desired metadata type for the table name.
     */
    protected function loadTableTypeMetadata(string $type, string $name): Constraint|array|TableSchemaInterface|null
    {
        return match ($type) {
            SchemaInterface::SCHEMA => $this->loadTableSchema($name),
            SchemaInterface::PRIMARY_KEY => $this->loadTablePrimaryKey($name),
            SchemaInterface::UNIQUES => $this->loadTableUniques($name),
            SchemaInterface::FOREIGN_KEYS => $this->loadTableForeignKeys($name),
            SchemaInterface::INDEXES => $this->loadTableIndexes($name),
            SchemaInterface::DEFAULT_VALUES => $this->loadTableDefaultValues($name),
            SchemaInterface::CHECKS => $this->loadTableChecks($name),
            default => null,
        };
    }

    /**
     * This method returns the desired metadata type for table name (with refresh if needed).
     *
     * @throws InvalidArgumentException
     */
    protected function getTableTypeMetadata(
        string $type,
        string $name,
        bool $refresh = false
    ): Constraint|array|null|TableSchemaInterface {
        return match ($type) {
            SchemaInterface::SCHEMA => $this->getTableSchema($name, $refresh),
            SchemaInterface::PRIMARY_KEY => $this->getTablePrimaryKey($name, $refresh),
            SchemaInterface::UNIQUES => $this->getTableUniques($name, $refresh),
            SchemaInterface::FOREIGN_KEYS => $this->getTableForeignKeys($name, $refresh),
            SchemaInterface::INDEXES => $this->getTableIndexes($name, $refresh),
            SchemaInterface::DEFAULT_VALUES => $this->getTableDefaultValues($name, $refresh),
            SchemaInterface::CHECKS => $this->getTableChecks($name, $refresh),
            default => null,
        };
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param string $name The table name.
     *
     * @throws NotSupportedException If the DBMS doesn't support this method.
     *
     * @return TableSchemaInterface The with resolved table, schema, etc. names.
     *
     * @see TableSchemaInterface
     */
    protected function resolveTableName(string $name): TableSchemaInterface
    {
        throw new NotSupportedException(static::class . ' does not support resolving table names.');
    }

    /**
     * Sets the metadata of the given type for the given table.
     *
     * @param string $name The table name.
     * @param string $type The metadata type.
     * @param mixed $data The metadata to set.
     */
    protected function setTableMetadata(string $name, string $type, mixed $data): void
    {
        /** @psalm-suppress MixedArrayAssignment */
        $this->tableMetadata[$this->db->getQuoter()->getRawTableName($name)][$type] = $data;
    }

    /**
     * Tries to load and populate table metadata from cache.
     *
     * @throws InvalidArgumentException
     */
    private function loadTableMetadataFromCache(string $rawName): void
    {
        if (!$this->schemaCache->isEnabled() || $this->schemaCache->isExcluded($rawName)) {
            $this->tableMetadata[$rawName] = [];
            return;
        }

        $metadata = $this->schemaCache->get($this->getCacheKey($rawName));

        if (
            !is_array($metadata) ||
            !isset($metadata[self::CACHE_VERSION]) ||
            $metadata[self::CACHE_VERSION] !== static::SCHEMA_CACHE_VERSION
        ) {
            $this->tableMetadata[$rawName] = [];
            return;
        }

        unset($metadata[self::CACHE_VERSION]);
        $this->tableMetadata[$rawName] = $metadata;
    }

    /**
     * Saves table metadata to cache.
     *
     * @throws InvalidArgumentException
     */
    private function saveTableMetadataToCache(string $rawName): void
    {
        if ($this->schemaCache->isEnabled() === false || $this->schemaCache->isExcluded($rawName) === true) {
            return;
        }

        /** @psalm-var array<string, array<TableSchemaInterface|int>> $metadata */
        $metadata = $this->tableMetadata[$rawName];
        /** @psalm-var int */
        $metadata[self::CACHE_VERSION] = static::SCHEMA_CACHE_VERSION;

        $this->schemaCache->set($this->getCacheKey($rawName), $metadata, $this->getCacheTag());
    }

    /**
     * Find the view names for the database.
     *
     * @param string $schema The schema of the views.
     * Defaults to empty string, meaning the current or default schema.
     *
     * @return array The names of all views in the database.
     */
    protected function findViewNames(string $schema = ''): array
    {
        return [];
    }

    /**
     * @throws Throwable
     *
     * @return array The view names for the database.
     */
    public function getViewNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->viewNames[$schema]) || $refresh) {
            $this->viewNames[$schema] = $this->findViewNames($schema);
        }

        return (array) $this->viewNames[$schema];
    }

    /**
     * @throws Throwable
     */
    public function hasTable(string $tableName, string $schema = '', bool $refresh = false): bool
    {
        $tables = $this->getTableNames($schema, $refresh);

        return in_array($tableName, $tables);
    }

    /**
     * @throws Throwable
     */
    public function hasSchema(string $schema, bool $refresh = false): bool
    {
        $schemas = $this->getSchemaNames($refresh);

        return in_array($schema, $schemas);
    }

    /**
     * @throws Throwable
     */
    public function hasView(string $viewName, string $schema = '', bool $refresh = false): bool
    {
        $views = $this->getViewNames($schema, $refresh);

        return in_array($viewName, $views);
    }
}
