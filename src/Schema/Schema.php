<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use PDO;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\NotSupportedException;

use function array_key_exists;
use function gettype;
use function is_array;
use function preg_match;

abstract class Schema implements SchemaInterface
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

    /**
     * Schema cache version, to detect incompatibilities in cached values when the data format of the cache changes.
     */
    protected const SCHEMA_CACHE_VERSION = 1;

    /**
     * @var string|null the default schema name used for the current session.
     */
    protected ?string $defaultSchema = null;
    private array $schemaNames = [];
    private array $tableNames = [];
    private array $tableMetadata = [];

    public function __construct(private SchemaCache $schemaCache)
    {
    }

    /**
     * Returns the cache key for the specified table name.
     *
     * @param string $name the table name.
     *
     * @return array the cache key.
     */
    abstract protected function getCacheKey(string $name): array;

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string the cache tag name.
     */
    abstract protected function getCacheTag(): string;

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
     * Loads a primary key for the given table.
     *
     * @param string $tableName table name.
     *
     * @return Constraint|null primary key for the given table, `null` if the table has no primary key.
     */
    abstract protected function loadTablePrimaryKey(string $tableName): ?Constraint;

    /**
     * Loads all unique constraints for the given table.
     *
     * @param string $tableName table name.
     *
     * @return array unique constraints for the given table.
     */
    abstract protected function loadTableUniques(string $tableName): array;

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name table name.
     *
     * @return TableSchema|null DBMS-dependent table metadata, `null` if the table does not exist.
     */
    abstract protected function loadTableSchema(string $name): ?TableSchema;

    public function getDefaultSchema(): ?string
    {
        return $this->defaultSchema;
    }

    public function getPdoType(mixed $data): int
    {
        static $typeMap = [
            // php type => PDO type
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'string' => PDO::PARAM_STR,
            'resource' => PDO::PARAM_LOB,
            'NULL' => PDO::PARAM_NULL,
        ];

        $type = gettype($data);

        return $typeMap[$type] ?? PDO::PARAM_STR;
    }

    public function getSchemaCache(): SchemaCache
    {
        return $this->schemaCache;
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaChecks(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'checks', $refresh);
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaDefaultValues(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'defaultValues', $refresh);
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaForeignKeys(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'foreignKeys', $refresh);
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaIndexes(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'indexes', $refresh);
    }

    public function getSchemaNames(bool $refresh = false): array
    {
        if (empty($this->schemaNames) || $refresh) {
            $this->schemaNames = $this->findSchemaNames();
        }

        return $this->schemaNames;
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaPrimaryKeys(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'primaryKey', $refresh);
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaUniques(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'uniques', $refresh);
    }

    public function getTableChecks(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'checks', $refresh);
    }

    public function getTableDefaultValues(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'defaultValues', $refresh);
    }

    public function getTableForeignKeys(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'foreignKeys', $refresh);
    }

    public function getTableIndexes(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'indexes', $refresh);
    }

    public function getTableNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->tableNames[$schema]) || $refresh) {
            $this->tableNames[$schema] = $this->findTableNames($schema);
        }

        return $this->tableNames[$schema];
    }

    public function getTablePrimaryKey(string $name, bool $refresh = false): ?Constraint
    {
        return $this->getTableMetadata($name, 'primaryKey', $refresh);
    }

    public function getTableSchema(string $name, bool $refresh = false): ?TableSchema
    {
        return $this->getTableMetadata($name, 'schema', $refresh);
    }

    public function getTableSchemas(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, 'schema', $refresh);
    }

    public function getTableUniques(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, 'uniques', $refresh);
    }

    /**
     * Returns a value indicating whether a SQL statement is for read purpose.
     *
     * @param string $sql the SQL statement.
     *
     * @return bool whether a SQL statement is for read purpose.
     */
    public function isReadQuery(string $sql): bool
    {
        $pattern = '/^\s*(SELECT|SHOW|DESCRIBE)\b/i';

        return preg_match($pattern, $sql) > 0;
    }

    /**
     * Refreshes the schema.
     *
     * This method cleans up all cached table schemas so that they can be re-created later to reflect the database
     * schema change.
     */
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
        $rawName = $this->getRawTableName($name);

        unset($this->tableMetadata[$rawName]);

        $this->tableNames = [];

        if ($this->schemaCache->isEnabled()) {
            $this->schemaCache->remove($this->getCacheKey($rawName));
        }
    }

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @throws NotSupportedException if this method is not supported by the DBMS.
     *
     * @return array all schema names in the database, except system schemas.
     */
    protected function findSchemaNames(): array
    {
        throw new NotSupportedException(static::class . ' does not support fetching all schema names.');
    }

    /**
     * Returns all table names in the database.
     *
     * This method should be overridden by child classes in order to support this feature because the default
     * implementation simply throws an exception.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     *
     * @throws NotSupportedException if this method is not supported by the DBMS.
     *
     * @return array all table names in the database. The names have NO schema name prefix.
     */
    protected function findTableNames(string $schema = ''): array
    {
        throw new NotSupportedException(static::class . ' does not support fetching all table names.');
    }

    /**
     * Extracts the PHP type from abstract DB type.
     *
     * @param ColumnSchema $column the column schema information.
     *
     * @return string PHP type name.
     */
    protected function getColumnPhpType(ColumnSchema $column): string
    {
        static $typeMap = [
            // abstract type => php type
            self::TYPE_TINYINT => 'integer',
            self::TYPE_SMALLINT => 'integer',
            self::TYPE_INTEGER => 'integer',
            self::TYPE_BIGINT => 'integer',
            self::TYPE_BOOLEAN => 'boolean',
            self::TYPE_FLOAT => 'double',
            self::TYPE_DOUBLE => 'double',
            self::TYPE_BINARY => 'resource',
            self::TYPE_JSON => 'array',
        ];

        if (isset($typeMap[$column->getType()])) {
            if ($column->getType() === 'bigint') {
                return PHP_INT_SIZE === 8 && !$column->isUnsigned() ? 'integer' : 'string';
            }

            if ($column->getType() === 'integer') {
                return PHP_INT_SIZE === 4 && $column->isUnsigned() ? 'string' : 'integer';
            }

            return $typeMap[$column->getType()];
        }

        return 'string';
    }

    /**
     * Returns the metadata of the given type for all tables in the given schema.
     *
     * @param string $schema the schema of the metadata. Defaults to empty string, meaning the current or default schema
     * name.
     * @param string $type metadata type.
     * @param bool $refresh whether to fetch the latest available table metadata. If this is `false`, cached data may be
     * returned if available.
     *
     * @throws NotSupportedException
     *
     * @return array array of metadata.
     */
    protected function getSchemaMetadata(string $schema, string $type, bool $refresh): array
    {
        $metadata = [];

        foreach ($this->getTableNames($schema, $refresh) as $name) {
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
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param string $type metadata type.
     * @param bool $refresh whether to reload the table metadata even if it is found in the cache.
     *
     * @return mixed metadata.
     */
    protected function getTableMetadata(string $name, string $type, bool $refresh = false): mixed
    {
        $rawName = $this->getRawTableName($name);

        if (!isset($this->tableMetadata[$rawName])) {
            $this->loadTableMetadataFromCache($rawName);
        }

        if ($refresh || !array_key_exists($type, $this->tableMetadata[$rawName])) {
            $this->tableMetadata[$rawName][$type] = $this->loadTableTypeMetadata($type, $rawName);
            $this->saveTableMetadataToCache($rawName);
        }

        return $this->tableMetadata[$rawName][$type];
    }

    /**
     * This method returns the desired metadata type for the table name.
     *
     * @param string $type
     * @param string $name
     *
     * @return array|Constraint|TableSchema|null
     */
    protected function loadTableTypeMetadata(string $type, string $name): Constraint|array|TableSchema|null
    {
        return match ($type) {
            self::SCHEMA => $this->loadTableSchema($name),
            self::PRIMARY_KEY => $this->loadTablePrimaryKey($name),
            self::UNIQUES => $this->loadTableUniques($name),
            self::FOREIGN_KEYS => $this->loadTableForeignKeys($name),
            self::INDEXES => $this->loadTableIndexes($name),
            self::DEFAULT_VALUES => $this->loadTableDefaultValues($name),
            self::CHECKS => $this->loadTableChecks($name),
            default => null,
        };
    }

    /**
     * This method returns the desired metadata type for table name (with refresh if needed)
     *
     * @param string $type
     * @param string $name
     * @param bool $refresh
     *
     * @return array|Constraint|TableSchema|null
     */
    protected function getTableTypeMetadata(
        string $type,
        string $name,
        bool $refresh = false
    ): Constraint|array|null|TableSchema {
        return match ($type) {
            self::SCHEMA => $this->getTableSchema($name, $refresh),
            self::PRIMARY_KEY => $this->getTablePrimaryKey($name, $refresh),
            self::UNIQUES => $this->getTableUniques($name, $refresh),
            self::FOREIGN_KEYS => $this->getTableForeignKeys($name, $refresh),
            self::INDEXES => $this->getTableIndexes($name, $refresh),
            self::DEFAULT_VALUES => $this->getTableDefaultValues($name, $refresh),
            self::CHECKS => $this->getTableChecks($name, $refresh),
            default => null,
        };
    }

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param string $name the table name.
     *
     * @throws NotSupportedException if this method is not supported by the DBMS.
     *
     * @return TableSchema with resolved table, schema, etc. names.
     *
     * {@see \Yiisoft\Db\Schema\TableSchema}
     */
    protected function resolveTableName(string $name): TableSchema
    {
        throw new NotSupportedException(static::class . ' does not support resolving table names.');
    }

    /**
     * Sets the metadata of the given type for the given table.
     *
     * @param string $name table name.
     * @param string $type metadata type.
     * @param mixed $data metadata.
     */
    protected function setTableMetadata(string $name, string $type, mixed $data): void
    {
        $this->tableMetadata[$this->getRawTableName($name)][$type] = $data;
    }

    /**
     * Tries to load and populate table metadata from cache.
     *
     * @param string $rawName
     */
    private function loadTableMetadataFromCache(string $rawName): void
    {
        if (!$this->schemaCache->isEnabled() || $this->schemaCache->isExcluded($rawName)) {
            $this->tableMetadata[$rawName] = [];
            return;
        }

        $metadata = $this->schemaCache->getOrSet(
            $this->getCacheKey($rawName),
            null,
            $this->schemaCache->getDuration(),
            new TagDependency($this->getCacheTag()),
        );

        if (
            !is_array($metadata) ||
            !isset($metadata['cacheVersion']) ||
            $metadata['cacheVersion'] !== static::SCHEMA_CACHE_VERSION
        ) {
            $this->tableMetadata[$rawName] = [];

            return;
        }

        unset($metadata['cacheVersion']);
        $this->tableMetadata[$rawName] = $metadata;
    }

    /**
     * Saves table metadata to cache.
     *
     * @param string $rawName
     */
    private function saveTableMetadataToCache(string $rawName): void
    {
        if ($this->schemaCache->isEnabled() === false || $this->schemaCache->isExcluded($rawName) === true) {
            return;
        }

        $metadata = $this->tableMetadata[$rawName];

        $metadata['cacheVersion'] = static::SCHEMA_CACHE_VERSION;

        $this->schemaCache->set(
            $this->getCacheKey($rawName),
            $metadata,
            $this->schemaCache->getDuration(),
            new TagDependency($this->getCacheTag()),
        );
    }
}
