<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use PDO;
use PDOException;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryBuilder;

use function addcslashes;
use function array_change_key_case;
use function array_key_exists;
use function array_map;
use function explode;
use function gettype;
use function implode;
use function is_array;
use function is_string;
use function md5;
use function preg_match;
use function preg_replace;
use function serialize;
use function str_replace;
use function strlen;
use function strpos;
use function substr;

/**
 * Schema is the base class for concrete DBMS-specific schema classes.
 *
 * Schema represents the database schema information that is DBMS specific.
 *
 * @property string $lastInsertID The row ID of the last row inserted, or the last value retrieved from the sequence
 * object. This property is read-only.
 * @property QueryBuilder $queryBuilder The query builder for this connection. This property is read-only.
 * @property string[] $schemaNames All schema names in the database, except system schemas. This property is read-only.
 * @property string $serverVersion Server version as a string. This property is read-only.
 * @property string[] $tableNames All table names in the database. This property is read-only.
 * @property TableSchema[] $tableSchemas The metadata for all tables in the database. Each array element is an instance
 * of {@see TableSchema} or its child class. This property is read-only.
 * @property string $transactionIsolationLevel The transaction isolation level to use for this transaction. This can be
 * one of {@see Transaction::READ_UNCOMMITTED}, {@see Transaction::READ_COMMITTED},
 * {@see Transaction::REPEATABLE_READ} and {@see Transaction::SERIALIZABLE} but also a string containing DBMS specific
 * syntax to be used after `SET TRANSACTION ISOLATION LEVEL`. This property is write-only.
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
abstract class Schema implements SchemaInterface
{
    /**
     * Schema cache version, to detect incompatibilities in cached values when the data format of the cache changes.
     */
    protected const SCHEMA_CACHE_VERSION = 1;

    /**
     * @var string|null the default schema name used for the current session.
     */
    protected ?string $defaultSchema = null;

    /**
     * @var array map of DB errors and corresponding exceptions. If left part is found in DB error message exception
     * class from the right part is used.
     */
    protected array $exceptionMap = [
        'SQLSTATE[23' => IntegrityException::class,
    ];

    /**
     * @var string|string[] character used to quote schema, table, etc. names. An array of 2 characters can be used in
     * case starting and ending characters are different.
     */
    protected $tableQuoteCharacter = "'";

    /**
     * @var string|string[] character used to quote column names. An array of 2 characters can be used in case starting
     * and ending characters are different.
     */
    protected $columnQuoteCharacter = '"';
    private array $schemaNames = [];
    private array $tableNames = [];
    private array $tableMetadata = [];
    private ?string $serverVersion = null;
    private ConnectionInterface $db;
    private ?QueryBuilder $builder = null;
    private SchemaCache $schemaCache;

    public function __construct(ConnectionInterface $db, SchemaCache $schemaCache)
    {
        $this->db = $db;
        $this->schemaCache = $schemaCache;
    }

    abstract public function createQueryBuilder(): QueryBuilder;

    /**
     * @inheritDoc
     */
    public function getQueryBuilder(): QueryBuilder
    {
        if ($this->builder === null) {
            $this->builder = $this->createQueryBuilder();
        }

        return $this->builder;
    }

    public function getDb(): ConnectionInterface
    {
        return $this->db;
    }

    public function getDefaultSchema(): ?string
    {
        return $this->defaultSchema;
    }

    public function getSchemaCache(): SchemaCache
    {
        return $this->schemaCache;
    }

    /**
     * @inheritDoc
     */
    public function getTableSchema(string $name, bool $refresh = false): ?TableSchema
    {
        return $this->getTableMetadata($name, self::SCHEMA, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getTableSchemas(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, self::SCHEMA, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaNames(bool $refresh = false): array
    {
        if (empty($this->schemaNames) || $refresh) {
            $this->schemaNames = $this->findSchemaNames();
        }

        return $this->schemaNames;
    }

    /**
     * @inheritDoc
     */
    public function getTableNames(string $schema = '', bool $refresh = false): array
    {
        if (!isset($this->tableNames[$schema]) || $refresh) {
            $this->tableNames[$schema] = $this->findTableNames($schema);
        }

        return $this->tableNames[$schema];
    }

    /**
     * @inheritDoc
     */
    public function getPdoType($data): int
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

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
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
     * @inheritDoc
     */
    public function getLastInsertID(string $sequenceName = ''): string
    {
        if ($this->db->isActive()) {
            return $this->db->getPDO()->lastInsertId(
                $sequenceName === '' ? null : $this->quoteTableName($sequenceName)
            );
        }

        throw new InvalidCallException('DB Connection is not active.');
    }

    /**
     * @inheritDoc
     */
    public function supportsSavepoint(): bool
    {
        return $this->db->isSavepointEnabled();
    }

    /**
     * @inheritDoc
     */
    public function createSavepoint(string $name): void
    {
        $this->db->createCommand("SAVEPOINT $name")->execute();
    }

    /**
     * @inheritDoc
     */
    public function releaseSavepoint(string $name): void
    {
        $this->db->createCommand("RELEASE SAVEPOINT $name")->execute();
    }

    /**
     * @inheritDoc
     */
    public function rollBackSavepoint(string $name): void
    {
        $this->db->createCommand("ROLLBACK TO SAVEPOINT $name")->execute();
    }

    /**
     * @inheritDoc
     */
    public function setTransactionIsolationLevel(string $level): void
    {
        $this->db->createCommand("SET TRANSACTION ISOLATION LEVEL $level")->execute();
    }

    /**
     * @inheritDoc
     */
    public function insert(string $table, array $columns)
    {
        $command = $this->db->createCommand()->insert($table, $columns);

        if (!$command->execute()) {
            return false;
        }

        $tableSchema = $this->getTableSchema($table);
        $result = [];

        foreach ($tableSchema->getPrimaryKey() as $name) {
            if ($tableSchema->getColumn($name)->isAutoIncrement()) {
                $result[$name] = $this->getLastInsertID($tableSchema->getSequenceName());
                break;
            }

            $result[$name] = $columns[$name] ?? $tableSchema->getColumn($name)->getDefaultValue();
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function quoteValue($str)
    {
        if (!is_string($str)) {
            return $str;
        }

        if (($value = $this->db->getSlavePdo()->quote($str)) !== false) {
            return $value;
        }

        /** the driver doesn't support quote (e.g. oci) */
        return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
    }

    /**
     * @inheritDoc
     */
    public function quoteTableName(string $name): string
    {
        if (strpos($name, '(') === 0 && strpos($name, ')') === strlen($name) - 1) {
            return $name;
        }

        if (strpos($name, '{{') !== false) {
            return $name;
        }

        if (strpos($name, '.') === false) {
            return $this->quoteSimpleTableName($name);
        }

        $parts = $this->getTableNameParts($name);

        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }

        return implode('.', $parts);
    }

    /**
     * @inheritDoc
     */
    public function quoteColumnName(string $name): string
    {
        if (strpos($name, '(') !== false || strpos($name, '[[') !== false) {
            return $name;
        }

        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }

        if (strpos($name, '{{') !== false) {
            return $name;
        }

        return $prefix . $this->quoteSimpleColumnName($name);
    }

    /**
     * @inheritDoc
     */
    public function quoteSimpleTableName(string $name): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->tableQuoteCharacter;
        } else {
            [$startingCharacter, $endingCharacter] = $this->tableQuoteCharacter;
        }

        return strpos($name, $startingCharacter) !== false ? $name : $startingCharacter . $name . $endingCharacter;
    }

    /**
     * @inheritDoc
     */
    public function quoteSimpleColumnName(string $name): string
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->columnQuoteCharacter;
        } else {
            [$startingCharacter, $endingCharacter] = $this->columnQuoteCharacter;
        }

        return $name === '*' || strpos($name, $startingCharacter) !== false ? $name : $startingCharacter . $name
            . $endingCharacter;
    }

    /**
     * @inheritDoc
     */
    public function unquoteSimpleTableName(string $name): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $this->tableQuoteCharacter;
        } else {
            $startingCharacter = $this->tableQuoteCharacter[0];
        }

        return strpos($name, $startingCharacter) === false ? $name : substr($name, 1, -1);
    }

    /**
     * @inheritDoc
     */
    public function unquoteSimpleColumnName(string $name): string
    {
        if (is_string($this->columnQuoteCharacter)) {
            $startingCharacter = $this->columnQuoteCharacter;
        } else {
            $startingCharacter = $this->columnQuoteCharacter[0];
        }

        return strpos($name, $startingCharacter) === false ? $name : substr($name, 1, -1);
    }

    /**
     * @inheritDoc
     */
    public function getRawTableName(string $name): string
    {
        if (strpos($name, '{{') !== false) {
            $name = preg_replace('/{{(.*?)}}/', '\1', $name);

            return str_replace('%', $this->db->getTablePrefix(), $name);
        }

        return $name;
    }

    /**
     * @inheritDoc
     */
    public function convertException(\Exception $e, string $rawSql): Exception
    {
        if ($e instanceof Exception) {
            return $e;
        }

        $exceptionClass = Exception::class;

        foreach ($this->exceptionMap as $error => $class) {
            if (strpos($e->getMessage(), $error) !== false) {
                $exceptionClass = $class;
            }
        }

        $message = $e->getMessage() . "\nThe SQL being executed was: $rawSql";
        $errorInfo = $e instanceof PDOException ? $e->errorInfo : null;

        return new $exceptionClass($message, $errorInfo, $e);
    }

    /**
     * @inheritDoc
     */
    public function isReadQuery(string $sql): bool
    {
        $pattern = '/^\s*(SELECT|SHOW|DESCRIBE)\b/i';

        return preg_match($pattern, $sql) > 0;
    }

    /**
     * @inheritDoc
     */
    public function getServerVersion(): string
    {
        if ($this->serverVersion === null) {
            $this->serverVersion = $this->db->getSlavePdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        }

        return $this->serverVersion;
    }

    /**
     * @inheritDoc
     */
    public function getTablePrimaryKey(string $name, bool $refresh = false): ?Constraint
    {
        return $this->getTableMetadata($name, SchemaInterface::PRIMARY_KEY, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaPrimaryKeys(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::PRIMARY_KEY, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getTableForeignKeys(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, SchemaInterface::FOREIGN_KEYS, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaForeignKeys(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::FOREIGN_KEYS, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getTableIndexes(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, SchemaInterface::INDEXES, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaIndexes(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::INDEXES, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getTableUniques(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, SchemaInterface::UNIQUES, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaUniques(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::UNIQUES, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getTableChecks(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, SchemaInterface::CHECKS, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaChecks(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::CHECKS, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getTableDefaultValues(string $name, bool $refresh = false): array
    {
        return $this->getTableMetadata($name, SchemaInterface::DEFAULT_VALUES, $refresh);
    }

    /**
     * @inheritDoc
     */
    public function getSchemaDefaultValues(string $schema = '', bool $refresh = false): array
    {
        return $this->getSchemaMetadata($schema, SchemaInterface::DEFAULT_VALUES, $refresh);
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
     * Loads the metadata for the specified table.
     *
     * @param string $name table name.
     *
     * @return TableSchema|null DBMS-dependent table metadata, `null` if the table does not exist.
     */
    abstract protected function loadTableSchema(string $name): ?TableSchema;

    /**
     * Splits full table name into parts
     *
     * @param string $name
     *
     * @return array
     */
    protected function getTableNameParts(string $name): array
    {
        return explode('.', $name);
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
     * Returns the cache key for the specified table name.
     *
     * @param string $name the table name.
     *
     * @return array the cache key.
     */
    protected function getCacheKey(string $name): array
    {
        return [
            __CLASS__,
            $this->db->getDsn(),
            $this->db->getUsername(),
            $this->getRawTableName($name),
        ];
    }

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string the cache tag name.
     */
    protected function getCacheTag(): string
    {
        return md5(serialize([
            __CLASS__,
            $this->db->getDsn(),
            $this->db->getUsername(),
        ]));
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
    protected function getTableMetadata(string $name, string $type, bool $refresh = false)
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
     * Sets the metadata of the given type for the given table.
     *
     * @param string $name table name.
     * @param string $type metadata type.
     * @param mixed $data metadata.
     */
    protected function setTableMetadata(string $name, string $type, $data): void
    {
        $this->tableMetadata[$this->getRawTableName($name)][$type] = $data;
    }

    /**
     * Changes row's array key case to lower if PDO's one is set to uppercase.
     *
     * @param array $row row's array or an array of row's arrays.
     * @param bool $multiple whether multiple rows or a single row passed.
     *
     * @throws Exception
     *
     * @return array normalized row or rows.
     */
    protected function normalizePdoRowKeyCase(array $row, bool $multiple): array
    {
        if ($this->db->getSlavePdo()->getAttribute(PDO::ATTR_CASE) !== PDO::CASE_UPPER) {
            return $row;
        }

        if ($multiple) {
            return array_map(static function (array $row) {
                return array_change_key_case($row, CASE_LOWER);
            }, $row);
        }

        return array_change_key_case($row, CASE_LOWER);
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

    /**
     * This method returns the desired metadata type for the table name.
     *
     * @param string $type
     * @param string $name
     *
     * @return mixed
     */
    protected function loadTableTypeMetadata(string $type, string $name)
    {
        switch ($type) {
            case SchemaInterface::SCHEMA:
                return $this->loadTableSchema($name);
            case SchemaInterface::PRIMARY_KEY:
                return $this->loadTablePrimaryKey($name);
            case SchemaInterface::UNIQUES:
                return $this->loadTableUniques($name);
            case SchemaInterface::FOREIGN_KEYS:
                return $this->loadTableForeignKeys($name);
            case SchemaInterface::INDEXES:
                return $this->loadTableIndexes($name);
            case SchemaInterface::DEFAULT_VALUES:
                return $this->loadTableDefaultValues($name);
            case SchemaInterface::CHECKS:
                return $this->loadTableChecks($name);
        }

        return null;
    }

    /**
     * This method returns the desired metadata type for table name (with refresh if needed)
     *
     * @param string $type
     * @param string $name
     * @param bool $refresh
     *
     * @return mixed
     */
    protected function getTableTypeMetadata(string $type, string $name, bool $refresh = false)
    {
        switch ($type) {
            case SchemaInterface::SCHEMA:
                return $this->getTableSchema($name, $refresh);
            case SchemaInterface::PRIMARY_KEY:
                return $this->getTablePrimaryKey($name, $refresh);
            case SchemaInterface::UNIQUES:
                return $this->getTableUniques($name, $refresh);
            case SchemaInterface::FOREIGN_KEYS:
                return $this->getTableForeignKeys($name, $refresh);
            case SchemaInterface::INDEXES:
                return $this->getTableIndexes($name, $refresh);
            case SchemaInterface::DEFAULT_VALUES:
                return $this->getTableDefaultValues($name, $refresh);
            case SchemaInterface::CHECKS:
                return $this->getTableChecks($name, $refresh);
        }

        return null;
    }

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
}
