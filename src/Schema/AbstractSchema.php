<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Check;
use Yiisoft\Db\Constraint\DefaultValue;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_filter;
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
     * @var string $defaultSchema The default schema name used for the current session.
     */
    protected string $defaultSchema = '';
    /**
     * @var (ColumnInterface|null)[] Saved columns from query results.
     * @psalm-var array<string, ColumnInterface|null>
     */
    protected array $resultColumns = [];
    /** @var string[][] */
    protected array $viewNames = [];
    /** @var string[] */
    private array $schemaNames = [];
    /** @var string[][] */
    private array $tableNames = [];
    /** @var (Check[]|DefaultValue[]|ForeignKey[]|Index|Index[]|TableSchemaInterface|null)[][] */
    private array $tableMetadata = [];

    public function __construct(protected ConnectionInterface $db, private SchemaCache $schemaCache) {}

    public function getDefaultSchema(): string
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

    final public function getResultColumn(array $metadata): ?ColumnInterface
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

    public function getSchemaChecks(string $schema = '', bool $refresh = false): array
    {
        /** @var Check[] */
        return $this->getSchemaMetadata($schema, SchemaInterface::CHECKS, $refresh);
    }

    public function getSchemaDefaultValues(string $schema = '', bool $refresh = false): array
    {
        /** @var DefaultValue[] */
        return $this->getSchemaMetadata($schema, SchemaInterface::DEFAULT_VALUES, $refresh);
    }

    public function getSchemaForeignKeys(string $schema = '', bool $refresh = false): array
    {
        /** @var ForeignKey[] */
        return $this->getSchemaMetadata($schema, SchemaInterface::FOREIGN_KEYS, $refresh);
    }

    public function getSchemaIndexes(string $schema = '', bool $refresh = false): array
    {
        /** @var Index[] */
        return $this->getSchemaMetadata($schema, SchemaInterface::INDEXES, $refresh);
    }

    public function getSchemaNames(bool $refresh = false): array
    {
        if (empty($this->schemaNames) || $refresh) {
            $this->schemaNames = $this->findSchemaNames();
        }

        return $this->schemaNames;
    }

    public function getSchemaPrimaryKeys(string $schema = '', bool $refresh = false): array
    {
        /** @var Index[] */
        return $this->getSchemaMetadata($schema, SchemaInterface::PRIMARY_KEY, $refresh);
    }

    public function getSchemaUniques(string $schema = '', bool $refresh = false): array
    {
        /** @var Index[] */
        return $this->getSchemaMetadata($schema, SchemaInterface::UNIQUES, $refresh);
    }

    public function getTableChecks(string $name, bool $refresh = false): array
    {
        /** @var Check[] */
        return $this->getTableMetadata($this->clearFullName($name), SchemaInterface::CHECKS, $refresh);
    }

    public function getTableDefaultValues(string $name, bool $refresh = false): array
    {
        /** @var DefaultValue[] */
        return $this->getTableMetadata($this->clearFullName($name), SchemaInterface::DEFAULT_VALUES, $refresh);
    }

    public function getTableForeignKeys(string $name, bool $refresh = false): array
    {
        /** @var ForeignKey[] */
        return $this->getTableMetadata($this->clearFullName($name), SchemaInterface::FOREIGN_KEYS, $refresh);
    }

    public function getTableIndexes(string $name, bool $refresh = false): array
    {
        /** @var Index[] */
        return $this->getTableMetadata($this->clearFullName($name), SchemaInterface::INDEXES, $refresh);
    }

    public function getTableNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->tableNames[$schema]) || $refresh) {
            $this->tableNames[$schema] = $this->findTableNames($schema);
        }

        return $this->tableNames[$schema];
    }

    public function getTablePrimaryKey(string $name, bool $refresh = false): ?Index
    {
        foreach ($this->getTableIndexes($name, $refresh) as $index) {
            if ($index->isPrimaryKey) {
                return $index;
            }
        }

        return null;
    }

    public function getTableSchema(string $name, bool $refresh = false): ?TableSchemaInterface
    {
        $rawName = $this->clearFullName($name);

        if ($refresh) {
            // Some constraints are loading and caching together.
            // Reset the table constraint cache to load them without refreshing.
            $this->tableMetadata[$rawName] = [];

            if ($this->schemaCache->isEnabled()) {
                $this->schemaCache->remove($this->getCacheKey($rawName));
            }
        }

        /** @var TableSchemaInterface|null */
        return $this->getTableMetadata($rawName, SchemaInterface::SCHEMA, $refresh);
    }

    public function getTableSchemas(string $schema = '', bool $refresh = false): array
    {
        /** @var TableSchemaInterface[] */
        return $this->getSchemaMetadata($schema, SchemaInterface::SCHEMA, $refresh);
    }

    public function getTableUniques(string $name, bool $refresh = false): array
    {
        return array_filter(
            $this->getTableIndexes($name, $refresh),
            static fn(Index $index): bool => $index->isUnique,
        );
    }

    public function refresh(): void
    {
        if ($this->schemaCache->isEnabled()) {
            $this->schemaCache->invalidate($this->getCacheTag());
        }

        $this->tableNames = [];
        $this->tableMetadata = [];
    }

    public function refreshTableSchema(string $name): void
    {
        $rawName = $this->clearFullName($name);

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

    public function getViewNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->viewNames[$schema]) || $refresh) {
            $this->viewNames[$schema] = $this->findViewNames($schema);
        }

        return $this->viewNames[$schema];
    }

    public function hasTable(string $tableName, string $schema = '', bool $refresh = false): bool
    {
        $tables = $this->getTableNames($schema, $refresh);

        return in_array($tableName, $tables);
    }

    public function hasSchema(string $schema, bool $refresh = false): bool
    {
        $schemas = $this->getSchemaNames($refresh);

        return in_array($schema, $schemas);
    }

    public function hasView(string $viewName, string $schema = '', bool $refresh = false): bool
    {
        $views = $this->getViewNames($schema, $refresh);

        return in_array($viewName, $views);
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
    abstract protected function loadResultColumn(array $metadata): ?ColumnInterface;

    /**
     * Loads all check constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return Check[] The check constraints for the given table.
     */
    abstract protected function loadTableChecks(string $tableName): array;

    /**
     * Loads all default value constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return DefaultValue[] The default value constraints for the given table.
     */
    abstract protected function loadTableDefaultValues(string $tableName): array;

    /**
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return ForeignKey[] The foreign keys for the given table, indexed by constraint name.
     */
    abstract protected function loadTableForeignKeys(string $tableName): array;

    /**
     * Loads all indexes for the given table.
     *
     * @param string $tableName The table name.
     *
     * @return Index[] The indexes for the given table.
     */
    abstract protected function loadTableIndexes(string $tableName): array;

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name The table name.
     *
     * @return TableSchemaInterface|null DBMS-dependent table metadata, `null` if the table doesn't exist.
     */
    abstract protected function loadTableSchema(string $name): ?TableSchemaInterface;

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     *
     * This method should be overridden by child classes to support this feature because the default
     * implementation simply throws an exception.
     *
     * @throws NotSupportedException If the DBMS doesn't support this method.
     *
     * @return string[] All schemas name in the database, except system schemas.
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
     * @return string[] All tables name in the database. The names have NO schema name prefix.
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
     * @return Check[][]|DefaultValue[][]|ForeignKey[][]|Index[]|Index[][]|TableSchemaInterface[] The metadata of the given type for all
     * tables in the given schema.
     */
    protected function getSchemaMetadata(string $schema, string $type, bool $refresh): array
    {
        $metadata = [];
        $quoter = $this->db->getQuoter();
        $tableNames = $this->getTableNames($schema, $refresh);

        foreach ($tableNames as $name) {
            $name = $quoter->quoteSimpleTableName($name);

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
     * @return Check[]|DefaultValue[]|ForeignKey[]|Index|Index[]|TableSchemaInterface|null The metadata of the given type
     * for the given table.
     *
     * @psalm-return (
     *     $type is SchemaInterface::CHECKS ? Check[] :
     *     $type is SchemaInterface::DEFAULT_VALUES ? DefaultValue[] :
     *     $type is SchemaInterface::FOREIGN_KEYS ? ForeignKey[] :
     *     $type is SchemaInterface::INDEXES ? Index[] :
     *     $type is SchemaInterface::PRIMARY_KEY ? Index|null :
     *     TableSchemaInterface
     * )
     */
    protected function getTableMetadata(
        string $name,
        string $type,
        bool $refresh = false,
    ): array|Index|TableSchemaInterface|null {
        if (!isset($this->tableMetadata[$name])) {
            $this->loadTableMetadataFromCache($name);
        }

        if ($refresh || !isset($this->tableMetadata[$name][$type])) {
            $this->tableMetadata[$name][$type] = $this->loadTableTypeMetadata($type, $name);
            $this->saveTableMetadataToCache($name);
        }

        return $this->tableMetadata[$name][$type];
    }

    /**
     * This method returns the desired metadata type for the table name.
     *
     * @return Check[]|DefaultValue[]|ForeignKey[]|Index[]|TableSchemaInterface|null
     */
    protected function loadTableTypeMetadata(string $type, string $name): array|TableSchemaInterface|null
    {
        return match ($type) {
            SchemaInterface::SCHEMA => $this->loadTableSchema($name),
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
     * @return Check[]|DefaultValue[]|ForeignKey[]|Index|Index[]|TableSchemaInterface|null
     */
    protected function getTableTypeMetadata(
        string $type,
        string $name,
        bool $refresh = false,
    ): array|Index|TableSchemaInterface|null {
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
     * Clears the full name. Removes the schema name if it is the default schema name, removes curly brackets
     * from the name, and replaces the percentage character '%' with {@see ConnectionInterface::getTablePrefix()}.
     */
    protected function clearFullName(string $fullName): string
    {
        return $this->resolveFullName(...$this->db->getQuoter()->getTableNameParts($fullName));
    }

    /**
     * Find and initialize table constraints.
     *
     * @param TableSchemaInterface $table The table metadata.
     */
    protected function findConstraints(TableSchemaInterface $table): void
    {
        $tableName = $this->resolveFullName($table->getName(), $table->getSchemaName());

        $table->checks(...$this->getTableMetadata($tableName, SchemaInterface::CHECKS));
        $table->defaultValues(...$this->getTableMetadata($tableName, SchemaInterface::DEFAULT_VALUES));
        $table->foreignKeys(...$this->getTableMetadata($tableName, SchemaInterface::FOREIGN_KEYS));
        $table->indexes(...$this->getTableMetadata($tableName, SchemaInterface::INDEXES));
    }

    /**
     * Resolves the full table name, considering the default schema name. Removes curly brackets from the names,
     * and replaces the percentage character '%' with {@see ConnectionInterface::getTablePrefix()}.
     */
    protected function resolveFullName(string $name, string $schemaName = ''): string
    {
        $quoter = $this->db->getQuoter();
        $rawName = $quoter->getRawTableName($name);

        return match ($schemaName) {
            '', $this->defaultSchema => $rawName,
            default => $quoter->getRawTableName($schemaName) . ".$rawName",
        };
    }

    /**
     * Sets the metadata of the given type for the given table.
     *
     * @param string $rawName The raw table name.
     * @param string $type The metadata type.
     * @param Check[]|DefaultValue[]|ForeignKey[]|Index|Index[]|TableSchemaInterface|null $data The metadata to set.
     */
    protected function setTableMetadata(
        string $rawName,
        string $type,
        array|Index|TableSchemaInterface|null $data,
    ): void {
        $this->tableMetadata[$rawName][$type] = $data;
    }

    /**
     * Find the view names for the database.
     *
     * @param string $schema The schema of the views.
     * Defaults to empty string, meaning the current or default schema.
     *
     * @return string[] The names of all views in the database.
     */
    protected function findViewNames(string $schema = ''): array
    {
        return [];
    }

    /**
     * Tries to load and populate table metadata from cache.
     */
    private function loadTableMetadataFromCache(string $rawName): void
    {
        if (!$this->schemaCache->isEnabled() || $this->schemaCache->isExcluded($rawName)) {
            $this->tableMetadata[$rawName] = [];
            return;
        }

        $metadata = $this->schemaCache->get($this->getCacheKey($rawName));

        if (
            !is_array($metadata)
            || !isset($metadata[self::CACHE_VERSION])
            || $metadata[self::CACHE_VERSION] !== static::SCHEMA_CACHE_VERSION
        ) {
            $this->tableMetadata[$rawName] = [];
            return;
        }

        unset($metadata[self::CACHE_VERSION]);
        /** @var (Check[]|DefaultValue[]|ForeignKey[]|Index|Index[]|TableSchemaInterface|null)[] $metadata */
        $this->tableMetadata[$rawName] = $metadata;
    }

    /**
     * Saves table metadata to cache.
     */
    private function saveTableMetadataToCache(string $rawName): void
    {
        if ($this->schemaCache->isEnabled() === false || $this->schemaCache->isExcluded($rawName) === true) {
            return;
        }

        $metadata = $this->tableMetadata[$rawName];
        $metadata[self::CACHE_VERSION] = static::SCHEMA_CACHE_VERSION;

        $this->schemaCache->set($this->getCacheKey($rawName), $metadata, $this->getCacheTag());
    }
}
