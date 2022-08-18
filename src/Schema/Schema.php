<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use PDO;
use Throwable;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\NotSupportedException;

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

    public const PHP_TYPE_INTEGER = 'integer';
    public const PHP_TYPE_STRING = 'string';
    public const PHP_TYPE_BOOLEAN = 'boolean';
    public const PHP_TYPE_DOUBLE = 'double';
    public const PHP_TYPE_RESOURCE = 'resource';
    public const PHP_TYPE_ARRAY = 'array';
    public const PHP_TYPE_NULL = 'NULL';

    public const CACHE_VERSION = 'cacheVersion';

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
    protected array $viewNames = [];
    private array $tableMetadata = [];

    public function __construct(protected ConnectionInterface $db, private SchemaCache $schemaCache)
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
     * @return TableSchemaInterface|null DBMS-dependent table metadata, `null` if the table does not exist.
     */
    abstract protected function loadTableSchema(string $name): ?TableSchemaInterface;

    public function getDefaultSchema(): ?string
    {
        return $this->defaultSchema;
    }

    public function getPdoType(mixed $data): int
    {
        /** @psalm-var array<string, int> */
        $typeMap = [
            // php type => PDO type
            self::PHP_TYPE_BOOLEAN => PDO::PARAM_BOOL,
            self::PHP_TYPE_INTEGER => PDO::PARAM_INT,
            self::PHP_TYPE_STRING => PDO::PARAM_STR,
            self::PHP_TYPE_RESOURCE => PDO::PARAM_LOB,
            self::PHP_TYPE_NULL => PDO::PARAM_NULL,
        ];

        $type = gettype($data);

        return $typeMap[$type] ?? PDO::PARAM_STR;
    }

    public function getRawTableName(string $name): string
    {
        if (str_contains($name, '{{')) {
            $name = preg_replace('/{{(.*?)}}/', '\1', $name);

            return str_replace('%', $this->db->getTablePrefix(), $name);
        }

        return $name;
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
        return $this->getSchemaMetadata($schema, self::CHECKS, $refresh);
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaDefaultValues(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, self::DEFAULT_VALUES, $refresh);
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaForeignKeys(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, self::FOREIGN_KEYS, $refresh);
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaIndexes(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, self::INDEXES, $refresh);
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
        return $this->getSchemaMetadata($schema, self::PRIMARY_KEY, $refresh);
    }

    /**
     * @throws NotSupportedException
     */
    public function getSchemaUniques(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, self::UNIQUES, $refresh);
    }

    public function getTableChecks(string $name, bool $refresh = false): array
    {
        /** @var mixed */
        $tableChecks = $this->getTableMetadata($name, self::CHECKS, $refresh);
        return is_array($tableChecks) ? $tableChecks : [];
    }

    public function getTableDefaultValues(string $name, bool $refresh = false): array
    {
        /** @var mixed */
        $tableDefaultValues = $this->getTableMetadata($name, self::DEFAULT_VALUES, $refresh);
        return is_array($tableDefaultValues) ? $tableDefaultValues : [];
    }

    public function getTableForeignKeys(string $name, bool $refresh = false): array
    {
        /** @var mixed */
        $tableForeignKeys = $this->getTableMetadata($name, self::FOREIGN_KEYS, $refresh);
        return is_array($tableForeignKeys) ? $tableForeignKeys : [];
    }

    public function getTableIndexes(string $name, bool $refresh = false): array
    {
        /** @var mixed */
        $tableIndexes = $this->getTableMetadata($name, self::INDEXES, $refresh);
        return is_array($tableIndexes) ? $tableIndexes : [];
    }

    public function getTableNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->tableNames[$schema]) || $refresh) {
            /** @psalm-var string[] */
            $this->tableNames[$schema] = $this->findTableNames($schema);
        }

        return is_array($this->tableNames[$schema]) ? $this->tableNames[$schema] : [];
    }

    public function getTablePrimaryKey(string $name, bool $refresh = false): ?Constraint
    {
        /** @var mixed */
        $tablePrimaryKey = $this->getTableMetadata($name, self::PRIMARY_KEY, $refresh);
        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    public function getTableSchema(string $name, bool $refresh = false): ?TableSchemaInterface
    {
        /** @var mixed */
        $tableSchema = $this->getTableMetadata($name, self::SCHEMA, $refresh);
        return $tableSchema instanceof TableSchemaInterface ? $tableSchema : null;
    }

    public function getTableSchemas(string $schema = '', bool $refresh = false): array
    {
        /** @var mixed */
        $tableSchemas = $this->getSchemaMetadata($schema, self::SCHEMA, $refresh);
        return is_array($tableSchemas) ? $tableSchemas : [];
    }

    public function getTableUniques(string $name, bool $refresh = false): array
    {
        /** @var mixed */
        $tableUniques = $this->getTableMetadata($name, self::UNIQUES, $refresh);
        return is_array($tableUniques) ? $tableUniques : [];
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
    protected function findTableNames(string $schema): array
    {
        throw new NotSupportedException(static::class . ' does not support fetching all table names.');
    }

    /**
     * Extracts the PHP type from abstract DB type.
     *
     * @param ColumnSchemaInterface $column the column schema information.
     *
     * @return string PHP type name.
     */
    protected function getColumnPhpType(ColumnSchemaInterface $column): string
    {
        /** @psalm-var string[] */
        $typeMap = [
            // abstract type => php type
            self::TYPE_TINYINT => self::PHP_TYPE_INTEGER,
            self::TYPE_SMALLINT => self::PHP_TYPE_INTEGER,
            self::TYPE_INTEGER => self::PHP_TYPE_INTEGER,
            self::TYPE_BIGINT => self::PHP_TYPE_INTEGER,
            self::TYPE_BOOLEAN => self::PHP_TYPE_BOOLEAN,
            self::TYPE_FLOAT => self::PHP_TYPE_DOUBLE,
            self::TYPE_DOUBLE => self::PHP_TYPE_DOUBLE,
            self::TYPE_BINARY => self::PHP_TYPE_RESOURCE,
            self::TYPE_JSON => self::PHP_TYPE_ARRAY,
        ];

        if (isset($typeMap[$column->getType()])) {
            if ($column->getType() === self::TYPE_BIGINT) {
                return PHP_INT_SIZE === 8 && !$column->isUnsigned() ? self::PHP_TYPE_INTEGER : self::PHP_TYPE_STRING;
            }

            if ($column->getType() === self::TYPE_INTEGER) {
                return PHP_INT_SIZE === 4 && $column->isUnsigned() ? self::PHP_TYPE_STRING : self::PHP_TYPE_INTEGER;
            }

            return $typeMap[$column->getType()];
        }

        return self::PHP_TYPE_STRING;
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
     * @return array of metadata.
     *
     * @psalm-return list<Constraint|TableSchemaInterface|array>
     */
    protected function getSchemaMetadata(string $schema, string $type, bool $refresh): array
    {
        $metadata = [];
        /** @psalm-var string[] */
        $tableNames = $this->getTableNames($schema, $refresh);

        foreach ($tableNames as $name) {
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
     *
     * @psalm-suppress MixedArrayAccess
     * @psalm-suppress MixedArrayAssignment
     */
    protected function getTableMetadata(string $name, string $type, bool $refresh = false): mixed
    {
        $rawName = $this->getRawTableName($name);

        if (!isset($this->tableMetadata[$rawName])) {
            $this->loadTableMetadataFromCache($rawName);
        }

        if ($refresh || !isset($this->tableMetadata[$rawName][$type])) {
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
     * @return array|Constraint|TableSchemaInterface|null
     */
    protected function loadTableTypeMetadata(string $type, string $name): Constraint|array|TableSchemaInterface|null
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
     * @return array|Constraint|TableSchemaInterface|null
     */
    protected function getTableTypeMetadata(
        string $type,
        string $name,
        bool $refresh = false
    ): Constraint|array|null|TableSchemaInterface {
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
     * @return TableSchemaInterface with resolved table, schema, etc. names.
     *
     * {@see \Yiisoft\Db\Schema\TableSchemaInterface}
     */
    protected function resolveTableName(string $name): TableSchemaInterface
    {
        throw new NotSupportedException(static::class . ' does not support resolving table names.');
    }

    /**
     * Sets the metadata of the given type for the given table.
     *
     * @param string $name table name.
     * @param string $type metadata type.
     * @param mixed $data metadata.
     *
     * @psalm-suppress MixedArrayAssignment
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
     * @param string $rawName
     */
    private function saveTableMetadataToCache(string $rawName): void
    {
        if ($this->schemaCache->isEnabled() === false || $this->schemaCache->isExcluded($rawName) === true) {
            return;
        }

        /** @psalm-var array<string, array<TableSchemaInterface|int>> */
        $metadata = $this->tableMetadata[$rawName];
        /** @var int */
        $metadata[self::CACHE_VERSION] = static::SCHEMA_CACHE_VERSION;

        $this->schemaCache->set(
            $this->getCacheKey($rawName),
            $metadata,
            $this->schemaCache->getDuration(),
            new TagDependency($this->getCacheTag()),
        );
    }

    protected function findViewNames(string $schema = ''): array
    {
        return [];
    }

    /**
     * @throws Throwable
     */
    public function getViewNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->viewNames[$schema]) || $refresh) {
            $this->viewNames[$schema] = $this->findViewNames($schema);
        }

        return (array) $this->viewNames[$schema];
    }
}
