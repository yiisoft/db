<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Factory\DatabaseFactory;
use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\TableSchema;
use Yiisoft\Db\Transaction\Transaction;
use Yiisoft\Profiler\Profiler;

use function end;
use function is_array;

/**
 * Connection represents a connection to a database via [PDO](http://php.net/manual/en/book.pdo.php).
 *
 * Connection works together with {@see Command}, {@see DataReader} and {@see Transaction} to provide data access to
 * various DBMS in a common set of APIs. They are a thin wrapper of the
 * [PDO PHP extension](http://php.net/manual/en/book.pdo.php).
 *
 * Connection supports database replication and read-write splitting. In particular, a Connection component can be
 * configured with multiple {@see setMasters()} and {@see setSlaves()}. It will do load balancing and failover by
 * choosing appropriate servers. It will also automatically direct read operations to the slaves and write operations
 * to the masters.
 *
 * To establish a DB connection, set {@see dsn}, {@see setUsername()} and {@see setPassword}, and then call
 * {@see open()} to connect to the database server. The current state of the connection can be checked using
 * {@see $isActive}.
 *
 * The following example shows how to create a Connection instance and establish the DB connection:
 *
 * ```php
 * $connection = new \Yiisoft\Db\Mysql\Connection(
 *     $cache,
 *     $logger,
 *     $profiler,
 *     $dsn
 * );
 * $connection->open();
 * ```
 *
 * After the DB connection is established, one can execute SQL statements like the following:
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $posts = $command->queryAll();
 * $command = $connection->createCommand('UPDATE post SET status=1');
 * $command->execute();
 * ```
 *
 * One can also do prepared SQL execution and bind parameters to the prepared SQL.
 * When the parameters are coming from user input, you should use this approach to prevent SQL injection attacks. The
 * following is an example:
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post WHERE id=:id');
 * $command->bindValue(':id', $_GET['id']);
 * $post = $command->query();
 * ```
 *
 * For more information about how to perform various DB queries, please refer to {@see Command}.
 *
 * If the underlying DBMS supports transactions, you can perform transactional SQL queries like the following:
 *
 * ```php
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     // ... executing other SQL statements ...
 *     $transaction->commit();
 * } catch (Exceptions $e) {
 *     $transaction->rollBack();
 * }
 * ```
 *
 * You also can use shortcut for the above like the following:
 *
 * ```php
 * $connection->transaction(function () {
 *     $order = new Order($customer);
 *     $order->save();
 *     $order->addItems($items);
 * });
 * ```
 *
 * If needed you can pass transaction isolation level as a second parameter:
 *
 * ```php
 * $connection->transaction(function (Connection $db) {
 *     //return $db->...
 * }, Transaction::READ_UNCOMMITTED);
 * ```
 *
 * Connection is often used as an application component and configured in the container-di configuration like the
 * following:
 *
 * ```php
 * Connection::class => static function (ContainerInterface $container) {
 *     $connection = new Connection(
 *         $container->get(CacheInterface::class),
 *         $container->get(LoggerInterface::class),
 *         $container->get(Profiler::class),
 *         'mysql:host=127.0.0.1;dbname=demo;charset=utf8'
 *     );
 *
 *     $connection->setUsername(root);
 *     $connection->setPassword('');
 *
 *     return $connection;
 * },
 * ```
 *
 * The {@see dsn} property can be defined via configuration {@see \Yiisoft\Db\Connection\Dsn}:
 *
 * ```php
 * Connection::class => static function (ContainerInterface $container) {
 *     $dsn = new Dsn('mysql', '127.0.0.1', 'yiitest', '3306');
 *
 *     $connection = new Connection(
 *         $container->get(CacheInterface::class),
 *         $container->get(LoggerInterface::class),
 *         $container->get(Profiler::class),
 *         $dsn->getDsn()
 *     );
 *
 *     $connection->setUsername(root);
 *     $connection->setPassword('');
 *
 *     return $connection;
 * },
 * ```
 *
 * @property string $driverName Name of the DB driver.
 * @property bool $isActive Whether the DB connection is established. This property is read-only.
 * @property string $lastInsertID The row ID of the last row inserted, or the last value retrieved from the sequence
 * object. This property is read-only.
 * @property Connection $master The currently active master connection. `null` is returned if there is no master
 * available. This property is read-only.
 * @property PDO $masterPdo The PDO instance for the currently active master connection. This property is read-only.
 * @property QueryBuilder $queryBuilder The query builder for the current DB connection. Note that the type of this
 * property differs in getter and setter. See {@see getQueryBuilder()} and {@see setQueryBuilder()} for details.
 * @property Schema $schema The schema information for the database opened by this connection. This property is
 * read-only.
 * @property string $serverVersion Server version as a string. This property is read-only.
 * @property Connection $slave The currently active slave connection. `null` is returned if there is no slave
 * available and `$fallbackToMaster` is false. This property is read-only.
 * @property PDO $slavePdo The PDO instance for the currently active slave connection. `null` is returned if no slave
 * connection is available and `$fallbackToMaster` is false. This property is read-only.
 * @property Transaction|null $transaction The currently active transaction. Null if no active transaction. This
 * property is read-only.
 */
abstract class Connection implements ConnectionInterface
{
    private ?string $driverName = null;
    private string $dsn;
    private ?string $username = null;
    private ?string $password = null;
    private array $attributes = [];
    private ?PDO $pdo = null;
    private bool $enableSchemaCache = true;
    private int $schemaCacheDuration = 3600;
    private array $schemaCacheExclude = [];
    private ?CacheInterface $schemaCache;
    private bool $enableQueryCache = true;
    private ?CacheInterface $queryCache = null;
    private ?string $charset = null;
    private ?bool $emulatePrepare = null;
    private string $tablePrefix = '';
    private array $queryCacheInfo = [];
    private bool $enableSavepoint = true;
    private int $serverRetryInterval = 600;
    private bool $enableSlaves = true;
    private array $slaves = [];
    private array $masters = [];
    private bool $shuffleMasters = true;
    private bool $enableLogging = true;
    private bool $enableProfiling = true;
    private int $queryCacheDuration = 3600;
    private array $quotedTableNames = [];
    private array $quotedColumnNames = [];
    private ?Connection $master = null;
    private ?Connection $slave = null;
    private LoggerInterface $logger;
    private Profiler $profiler;
    private ?Transaction $transaction = null;
    private ?Schema $schema = null;

    public function __construct(CacheInterface $cache, LoggerInterface $logger, Profiler $profiler, string $dsn)
    {
        $this->schemaCache = $cache;
        $this->logger = $logger;
        $this->profiler = $profiler;
        $this->dsn = $dsn;
    }

    /**
     * Creates a command for execution.
     *
     * @param string|null $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     *
     * @throws Exception|InvalidConfigException
     *
     * @return Command the DB command
     */
    abstract public function createCommand(?string $sql = null, array $params = []): Command;

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     */
    abstract public function getSchema(): Schema;

    /**
     * Creates the PDO instance.
     *
     * This method is called by {@see open} to establish a DB connection. The default implementation will create a PHP
     * PDO instance. You may override this method if the default PDO needs to be adapted for certain DBMS.
     *
     * @return PDO the pdo instance
     */
    abstract protected function createPdoInstance(): PDO;

    /**
     * Initializes the DB connection.
     *
     * This method is invoked right after the DB connection is established.
     *
     * The default implementation turns on `PDO::ATTR_EMULATE_PREPARES`.
     *
     * if {@see emulatePrepare} is true, and sets the database {@see charset} if it is not empty.
     *
     * It then triggers an {@see EVENT_AFTER_OPEN} event.
     */
    abstract protected function initConnection(): void;

    /**
     * Reset the connection after cloning.
     */
    public function __clone()
    {
        $this->master = null;
        $this->slave = null;
        $this->schema = null;
        $this->transaction = null;

        if (strncmp($this->dsn, 'sqlite::memory:', 15) !== 0) {
            /** reset PDO connection, unless its sqlite in-memory, which can only have one connection */
            $this->pdo = null;
        }
    }

    /**
     * Close the connection before serializing.
     *
     * @return array
     */
    public function __sleep(): array
    {
        $fields = (array) $this;

        unset(
            $fields["\000" . __CLASS__ . "\000" . 'pdo'],
            $fields["\000" . __CLASS__ . "\000" . 'master'],
            $fields["\000" . __CLASS__ . "\000" . 'slave'],
            $fields["\000" . __CLASS__ . "\000" . 'transaction'],
            $fields["\000" . __CLASS__ . "\000" . 'schema']
        );

        return array_keys($fields);
    }

    /**
     * Starts a transaction.
     *
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     *
     * {@see Transaction::begin()} for details.
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return Transaction the transaction initiated
     */
    public function beginTransaction($isolationLevel = null): Transaction
    {
        $this->open();

        if (($transaction = $this->getTransaction()) === null) {
            $transaction = $this->transaction = new Transaction($this, $this->logger);
        }

        $transaction->begin($isolationLevel);

        return $transaction;
    }

    /**
     * Uses query cache for the queries performed with the callable.
     *
     * When query caching is enabled ({@see enableQueryCache} is true and {@see queryCache} refers to a valid cache),
     * queries performed within the callable will be cached and their results will be fetched from cache if available.
     *
     * For example,
     *
     * ```php
     * // The customer will be fetched from cache if available.
     * // If not, the query will be made against DB and cached for use next time.
     * $customer = $db->cache(function (Connection $db) {
     *     return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     * });
     * ```
     *
     * Note that query cache is only meaningful for queries that return results. For queries performed with
     * {@see Command::execute()}, query cache will not be used.
     *
     * @param callable $callable a PHP callable that contains DB queries which will make use of query cache.
     * The signature of the callable is `function (Connection $db)`.
     * @param int|null $duration the number of seconds that query results can remain valid in the cache. If this is not
     * set, the value of {@see queryCacheDuration} will be used instead. Use 0 to indicate that the cached data will
     * never expire.
     * @param Dependency|null $dependency the cache dependency associated with the cached query
     * results.
     *
     * @throws Throwable if there is any exception during query
     *
     * @return mixed the return result of the callable
     *
     * {@see setEnableQueryCache()}
     * {@see queryCache}
     * {@see noCache()}
     */
    public function cache(callable $callable, ?int $duration = null, ?Dependency $dependency = null)
    {
        $this->queryCacheInfo[] = [$duration ?? $this->queryCacheDuration, $dependency];

        try {
            $result = $callable($this);

            array_pop($this->queryCacheInfo);

            return $result;
        } catch (Throwable $e) {
            array_pop($this->queryCacheInfo);

            throw $e;
        }
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getEmulatePrepare(): ?bool
    {
        return $this->emulatePrepare;
    }

    public function isLoggingEnabled(): bool
    {
        return $this->enableLogging;
    }

    public function isProfilingEnabled(): bool
    {
        return $this->enableProfiling;
    }

    public function isQueryCacheEnabled(): bool
    {
        return $this->enableQueryCache;
    }

    public function isSavepointEnabled(): bool
    {
        return $this->enableSavepoint;
    }

    public function isSchemaCacheEnabled(): bool
    {
        return $this->enableSchemaCache;
    }

    public function areSlavesEnabled(): bool
    {
        return $this->enableSlaves;
    }

    /**
     * Returns a value indicating whether the DB connection is established.
     *
     * @return bool whether the DB connection is established
     */
    public function isActive(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     *
     * @throws Exception
     * @throws InvalidCallException
     *
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     *
     * {@see http://php.net/manual/en/pdo.lastinsertid.php'>http://php.net/manual/en/pdo.lastinsertid.php}
     */
    public function getLastInsertID($sequenceName = ''): string
    {
        return $this->getSchema()->getLastInsertID($sequenceName);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Returns the currently active master connection.
     *
     * If this method is called for the first time, it will try to open a master connection.
     *
     * @throws InvalidConfigException
     *
     * @return Connection the currently active master connection. `null` is returned if there is no master available.
     */
    public function getMaster(): ?Connection
    {
        if ($this->master === null) {
            $this->master = $this->shuffleMasters
                ? $this->openFromPool($this->masters)
                : $this->openFromPoolSequentially($this->masters);
        }

        return $this->master;
    }

    /**
     * Returns the PDO instance for the currently active master connection.
     *
     * This method will open the master DB connection and then return {@see pdo}.
     *
     * @throws Exception
     *
     * @return PDO the PDO instance for the currently active master connection.
     */
    public function getMasterPdo(): PDO
    {
        $this->open();

        return $this->pdo;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * The PHP PDO instance associated with this DB connection. This property is mainly managed by {@see open()} and
     * {@see close()} methods. When a DB connection is active, this property will represent a PDO instance; otherwise,
     * it will be null.
     *
     * @return PDO|null
     *
     * {@see pdoClass}
     */
    public function getPDO(): ?PDO
    {
        return $this->pdo;
    }

    public function getProfiler(): profiler
    {
        return $this->profiler;
    }

    /**
     * Returns the query builder for the current DB connection.
     *
     * @return QueryBuilder the query builder for the current DB connection.
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getSchema()->getQueryBuilder();
    }

    public function getQueryCacheDuration(): ?int
    {
        return $this->queryCacheDuration;
    }

    /**
     * Returns the current query cache information.
     *
     * This method is used internally by {@see Command}.
     *
     * @param int|null $duration the preferred caching duration. If null, it will be ignored.
     * @param Dependency|null $dependency the preferred caching dependency. If null, it will be
     * ignored.
     *
     * @return array|null the current query cache information, or null if query cache is not enabled.
     */
    public function getQueryCacheInfo(?int $duration, ?Dependency $dependency = null): ?array
    {
        $result = null;

        if ($this->enableQueryCache) {
            $info = end($this->queryCacheInfo);

            if (is_array($info)) {
                if ($duration === null) {
                    $duration = $info[0];
                }

                if ($dependency === null) {
                    $dependency = $info[1];
                }
            }

            if ($duration === 0 || $duration > 0) {
                if ($this->schemaCache instanceof CacheInterface) {
                    $result = [$this->schemaCache, $duration, $dependency];
                }
            }
        }

        return $result;
    }

    public function getSchemaCache(): CacheInterface
    {
        return $this->schemaCache;
    }

    public function getSchemaCacheDuration(): int
    {
        return $this->schemaCacheDuration;
    }

    public function getSchemaCacheExclude(): array
    {
        return $this->schemaCacheExclude;
    }

    /**
     * Returns a server version as a string comparable by {@see \version_compare()}.
     *
     * @return string server version as a string.
     */
    public function getServerVersion(): string
    {
        return $this->getSchema()->getServerVersion();
    }

    /**
     * Returns the currently active slave connection.
     *
     * If this method is called for the first time, it will try to open a slave connection when {@see setEnableSlaves()}
     * is true.
     *
     * @param bool $fallbackToMaster whether to return a master connection in case there is no slave connection
     * available.
     *
     * @throws InvalidConfigException
     *
     * @return Connection the currently active slave connection. `null` is returned if there is no slave available and
     * `$fallbackToMaster` is false.
     */
    public function getSlave(bool $fallbackToMaster = true): ?Connection
    {
        if (!$this->enableSlaves) {
            return $fallbackToMaster ? $this : null;
        }

        if ($this->slave === null) {
            $this->slave = $this->openFromPool($this->slaves);
        }

        return $this->slave === null && $fallbackToMaster ? $this : $this->slave;
    }

    /**
     * Returns the PDO instance for the currently active slave connection.
     *
     * When {@see enableSlaves} is true, one of the slaves will be used for read queries, and its PDO instance will be
     * returned by this method.
     *
     * @param bool $fallbackToMaster whether to return a master PDO in case none of the slave connections is available.
     *
     * @throws Exception|InvalidConfigException
     *
     * @return PDO the PDO instance for the currently active slave connection. `null` is returned if no slave connection
     * is available and `$fallbackToMaster` is false.
     */
    public function getSlavePdo(bool $fallbackToMaster = true): ?PDO
    {
        $db = $this->getSlave(false);

        if ($db === null) {
            return $fallbackToMaster ? $this->getMasterPdo() : null;
        }

        return $db->getPdo();
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * Obtains the schema information for the named table.
     *
     * @param string $name table name.
     * @param bool $refresh whether to reload the table schema even if it is found in the cache.
     *
     * @return TableSchema
     */
    public function getTableSchema(string $name, $refresh = false): ?TableSchema
    {
        return $this->getSchema()->getTableSchema($name, $refresh);
    }

    /**
     * Returns the currently active transaction.
     *
     * @return Transaction|null the currently active transaction. Null if no active transaction.
     */
    public function getTransaction(): ?Transaction
    {
        return $this->transaction && $this->transaction->isActive() ? $this->transaction : null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Disables query cache temporarily.
     *
     * Queries performed within the callable will not use query cache at all. For example,
     *
     * ```php
     * $db->cache(function (Connection $db) {
     *
     *     // ... queries that use query cache ...
     *
     *     return $db->noCache(function (Connection $db) {
     *         // this query will not use query cache
     *         return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     *     });
     * });
     * ```
     *
     * @param callable $callable a PHP callable that contains DB queries which should not use query cache. The signature
     * of the callable is `function (Connection $db)`.
     *
     * @throws Throwable if there is any exception during query
     *
     * @return mixed the return result of the callable
     *
     * {@see enableQueryCache}
     * {@see queryCache}
     * {@see cache()}
     */
    public function noCache(callable $callable)
    {
        $this->queryCacheInfo[] = false;

        try {
            $result = $callable($this);
            array_pop($this->queryCacheInfo);

            return $result;
        } catch (Throwable $e) {
            array_pop($this->queryCacheInfo);

            throw $e;
        }
    }

    /**
     * Establishes a DB connection.
     *
     * It does nothing if a DB connection has already been established.
     *
     * @throws Exception|InvalidConfigException if connection fails
     */
    public function open()
    {
        if (!empty($this->pdo)) {
            return null;
        }

        if (!empty($this->masters)) {
            $db = $this->getMaster();

            if ($db !== null) {
                $this->pdo = $db->getPDO();

                return null;
            }

            throw new InvalidConfigException('None of the master DB servers is available.');
        }

        if (empty($this->dsn)) {
            throw new InvalidConfigException('Connection::dsn cannot be empty.');
        }

        $token = 'Opening DB connection: ' . $this->dsn;

        try {
            if ($this->enableLogging) {
                $this->logger->log(LogLevel::INFO, $token);
            }

            if ($this->enableProfiling) {
                $this->profiler->begin($token, [__METHOD__]);
            }

            $this->pdo = $this->createPdoInstance();

            $this->initConnection();

            if ($this->enableProfiling) {
                $this->profiler->end($token, [__METHOD__]);
            }
        } catch (PDOException $e) {
            if ($this->enableProfiling) {
                $this->profiler->end($token, [__METHOD__]);
            }

            if ($this->enableLogging) {
                $this->logger->log(LogLevel::ERROR, $token);
            }

            throw new Exception($e->getMessage(), $e->errorInfo, $e);
        }
    }

    /**
     * Closes the currently active DB connection.
     *
     * It does nothing if the connection is already closed.
     */
    public function close(): void
    {
        if ($this->master) {
            if ($this->pdo === $this->master->getPDO()) {
                $this->pdo = null;
            }

            $this->master->close();

            $this->master = null;
        }

        if ($this->pdo !== null) {
            if ($this->enableLogging) {
                $this->logger->log(LogLevel::DEBUG, 'Closing DB connection: ' . $this->dsn . ' ' . __METHOD__);
            }

            $this->pdo = null;
            $this->schema = null;
            $this->transaction = null;
        }

        if ($this->slave) {
            $this->slave->close();
            $this->slave = null;
        }
    }

    /**
     * Rolls back given {@see Transaction} object if it's still active and level match. In some cases rollback can fail,
     * so this method is fail safe. Exceptions thrown from rollback will be caught and just logged with
     * {@see logger->log()}.
     *
     * @param Transaction $transaction Transaction object given from {@see beginTransaction()}.
     * @param int $level Transaction level just after {@see beginTransaction()} call.
     *
     * @return void
     */
    private function rollbackTransactionOnLevel(Transaction $transaction, int $level): void
    {
        if ($transaction->isActive() && $transaction->getLevel() === $level) {
            /**
             * {@see https://github.com/yiisoft/yii2/pull/13347}
             */
            try {
                $transaction->rollBack();
            } catch (Exception $e) {
                $this->logger->log(LogLevel::ERROR, $e, [__METHOD__]);
                /** hide this exception to be able to continue throwing original exception outside */
            }
        }
    }

    /**
     * Opens the connection to a server in the pool.
     *
     * This method implements the load balancing among the given list of the servers.
     *
     * Connections will be tried in random order.
     *
     * @param array $pool the list of connection configurations in the server pool
     *
     * @return Connection|null the opened DB connection, or `null` if no server is available
     */
    protected function openFromPool(array $pool): ?Connection
    {
        shuffle($pool);

        return $this->openFromPoolSequentially($pool);
    }

    /**
     * Opens the connection to a server in the pool.
     *
     * This method implements the load balancing among the given list of the servers.
     *
     * Connections will be tried in sequential order.
     *
     * @param array $pool
     *
     * @return Connection|null the opened DB connection, or `null` if no server is available
     */
    protected function openFromPoolSequentially(array $pool): ?Connection
    {
        if (!$pool) {
            return null;
        }

        foreach ($pool as $config) {
            /* @var $db Connection */
            $db = DatabaseFactory::createClass($config);

            $key = $this->getCacheKey([__METHOD__, $db->getDsn()]);

            if ($this->schemaCache instanceof CacheInterface && $this->schemaCache->get($key)) {
                /** should not try this dead server now */
                continue;
            }

            try {
                $db->open();

                return $db;
            } catch (Exception $e) {
                if ($this->enableLogging) {
                    $this->logger->log(
                        LogLevel::WARNING,
                        "Connection ({$db->getDsn()}) failed: " . $e->getMessage() . ' ' . __METHOD__
                    );
                }

                if ($this->schemaCache instanceof CacheInterface) {
                    /** mark this server as dead and only retry it after the specified interval */
                    $this->schemaCache->set($key, 1, $this->serverRetryInterval);
                }

                return null;
            }
        }

        return null;
    }

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains special characters including '(', '[[' and '{{', then this
     * method will do nothing.
     *
     * @param string $name column name
     *
     * @return string the properly quoted column name
     */
    public function quoteColumnName(string $name): string
    {
        return $this->quotedColumnNames[$name]
            ?? ($this->quotedColumnNames[$name] = $this->getSchema()->quoteColumnName($name));
    }

    /**
     * Processes a SQL statement by quoting table and column names that are enclosed within double brackets.
     *
     * Tokens enclosed within double curly brackets are treated as table names, while tokens enclosed within double
     * square brackets are column names. They will be quoted accordingly. Also, the percentage character "%" at the
     * beginning or ending of a table name will be replaced with {@see tablePrefix}.
     *
     * @param string $sql the SQL to be quoted
     *
     * @return string the quoted SQL
     */
    public function quoteSql(string $sql): string
    {
        return preg_replace_callback(
            '/({{(%?[\w\-. ]+%?)}}|\\[\\[([\w\-. ]+)]])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                }

                return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
            },
            $sql
        );
    }

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains special characters including '(', '[[' and '{{', then this method
     * will do nothing.
     *
     * @param string $name table name
     *
     * @return string the properly quoted table name
     */
    public function quoteTableName(string $name): string
    {
        return $this->quotedTableNames[$name]
            ?? ($this->quotedTableNames[$name] = $this->getSchema()->quoteTableName($name));
    }

    /**
     * Quotes a string value for use in a query.
     *
     * Note that if the parameter is not a string, it will be returned without change.
     *
     * @param string|int $value string to be quoted
     *
     * @return string|int the properly quoted string
     *
     * {@see http://php.net/manual/en/pdo.quote.php}
     */
    public function quoteValue($value)
    {
        return $this->getSchema()->quoteValue($value);
    }

    /**
     * PDO attributes (name => value) that should be set when calling {@see open()} to establish a DB connection.
     * Please refer to the [PHP manual](http://php.net/manual/en/pdo.setattribute.php) for details about available
     * attributes.
     *
     * @param array $value
     *
     * @return void
     */
    public function setAttributes(array $value): void
    {
        $this->attributes = $value;
    }

    /**
     * The charset used for database connection. The property is only used for MySQL, PostgreSQL databases. Defaults to
     * null, meaning using default charset as configured by the database.
     *
     * For Oracle Database, the charset must be specified in the {@see dsn}, for example for UTF-8 by appending
     * `;charset=UTF-8` to the DSN string.
     *
     * The same applies for if you're using GBK or BIG5 charset with MySQL, then it's highly recommended to specify
     * charset via {@see dsn} like `'mysql:dbname=mydatabase;host=127.0.0.1;charset=GBK;'`.
     *
     * @param string|null $value
     *
     * @return void
     */
    public function setCharset(?string $value): void
    {
        $this->charset = $value;
    }

    /**
     * Changes the current driver name.
     *
     * @param string $driverName name of the DB driver
     */
    public function setDriverName(string $driverName): void
    {
        $this->driverName = strtolower($driverName);
    }

    /**
     * Whether to turn on prepare emulation. Defaults to false, meaning PDO will use the native prepare support if
     * available. For some databases (such as MySQL), this may need to be set true so that PDO can emulate the prepare
     * support to bypass the buggy native prepare support. The default value is null, which means the PDO
     * ATTR_EMULATE_PREPARES value will not be changed.
     *
     * @param bool $value
     *
     * @return void
     */
    public function setEmulatePrepare(bool $value): void
    {
        $this->emulatePrepare = $value;
    }

    /**
     * Whether to enable logging of database queries. Defaults to true. You may want to disable this option in a
     * production environment to gain performance if you do not need the information being logged.
     *
     * @param bool $value
     *
     * @return void
     *
     * {@see setEnableProfiling()}
     */
    public function setEnableLogging(bool $value): void
    {
        $this->enableLogging = $value;
    }

    /**
     * Whether to enable profiling of opening database connection and database queries. Defaults to true. You may want
     * to disable this option in a production environment to gain performance if you do not need the information being
     * logged.
     *
     * @param bool $value
     *
     * @return void
     *
     * {@see setEnableLogging()}
     */
    public function setEnableProfiling(bool $value): void
    {
        $this->enableProfiling = $value;
    }

    /**
     * Whether to enable query caching. Note that in order to enable query caching, a valid cache component as specified
     * by {@see setQueryCache()} must be enabled and {@see enableQueryCache} must be set true. Also, only the results of
     * the queries enclosed within {@see cache()} will be cached.
     *
     * @param bool $value
     *
     * @return void
     *
     * {@see setQueryCache()}
     * {@see cache()}
     * {@see noCache()}
     */
    public function setEnableQueryCache(bool $value): void
    {
        $this->enableQueryCache = $value;
    }

    /**
     * Whether to enable [savepoint](http://en.wikipedia.org/wiki/Savepoint). Note that if the underlying DBMS does not
     * support savepoint, setting this property to be true will have no effect.
     *
     * @param bool $value
     *
     * @return void
     */
    public function setEnableSavepoint(bool $value): void
    {
        $this->enableSavepoint = $value;
    }

    /**
     * Whether to enable schema caching. Note that in order to enable truly schema caching, a valid cache component as
     * specified by {@see setSchemaCache()} must be enabled and {@see setEnableSchemaCache()} must be set true.
     *
     * @param bool $value
     *
     * @return void
     *
     * {@see setSchemaCacheDuration()}
     * {@see setSchemaCacheExclude()}
     * {@see setSchemaCache()}
     */
    public function setEnableSchemaCache(bool $value): void
    {
        $this->enableSchemaCache = $value;
    }

    /**
     * Whether to enable read/write splitting by using {@see setSlaves()} to read data. Note that if {@see setSlaves()}
     * is empty, read/write splitting will NOT be enabled no matter what value this property takes.
     *
     * @param bool $value
     *
     * @return void
     */
    public function setEnableSlaves(bool $value): void
    {
        $this->enableSlaves = $value;
    }

    /**
     * List of master connection. Each DSN is used to create a master DB connection. When {@see open()} is called, one
     * of these configurations will be chosen and used to create a DB connection which will be used by this object.
     *
     * @param string $key index master connection.
     * @param array $config The configuration that should be merged with every master configuration
     *
     * @return void
     *
     * For example,
     *
     * ```php
     * $connection->setMasters(
     *     '1',
     *     [
     *         '__construct()' => ['mysql:host=127.0.0.1;dbname=yiitest;port=3306'],
     *         'setUsername()' => [$connection->getUsername()],
     *         'setPassword()' => [$connection->getPassword()],
     *     ]
     * );
     * ```
     *
     * {@see setShuffleMasters()}
     */
    public function setMasters(string $key, array $config = []): void
    {
        $this->masters[$key] = $config;
    }

    /**
     * The password for establishing DB connection. Defaults to `null` meaning no password to use.
     *
     * @param string|null $value
     *
     * @return void
     */
    public function setPassword(?string $value): void
    {
        $this->password = $value;
    }

    /**
     * Can be used to set {@see QueryBuilder} configuration via Connection configuration array.
     *
     * @param iterable $config the {@see QueryBuilder} properties to be configured.
     *
     * @return void
     */
    public function setQueryBuilder(iterable $config): void
    {
        $builder = $this->getQueryBuilder();

        foreach ($config as $key => $value) {
            $builder->{$key} = $value;
        }
    }

    /**
     * The cache object or the ID of the cache application component that is used for query caching.
     *
     * @param CacheInterface $value
     *
     * @return void
     *
     * {@see setEnableQueryCache()}
     */
    public function setQueryCache(CacheInterface $value): void
    {
        $this->queryCache = $value;
    }

    /**
     * The default number of seconds that query results can remain valid in cache. Defaults to 3600, meaning 3600
     * seconds, or one hour. Use 0 to indicate that the cached data will never expire. The value of this property will
     * be used when {@see cache()} is called without a cache duration.
     *
     * @param int $value
     *
     * @return void
     *
     * {@see setEnableQueryCache()}
     * {@see cache()}
     */
    public function setQueryCacheDuration(int $value): void
    {
        $this->queryCacheDuration = $value;
    }

    /**
     * The cache object or the ID of the cache application component that is used to cache the table metadata.
     *
     * @param CacheInterface $value
     *
     * @return void
     *
     * {@see setEnableSchemaCache()}
     */
    public function setSchemaCache(?CacheInterface $value): void
    {
        $this->schemaCache = $value;
    }

    /**
     * Number of seconds that table metadata can remain valid in cache. Use 0 to indicate that the cached data will
     * never expire.
     *
     * @param int $value
     *
     * @return void
     *
     * {@see setEnableSchemaCache()}
     */
    public function setSchemaCacheDuration(int $value): void
    {
        $this->schemaCacheDuration = $value;
    }

    /**
     * List of tables whose metadata should NOT be cached. Defaults to empty array. The table names may contain schema
     * prefix, if any. Do not quote the table names.
     *
     * @param array $value
     *
     * @return void
     *
     * {@see setEnableSchemaCache()}
     */
    public function setSchemaCacheExclude(array $value): void
    {
        $this->schemaCacheExclude = $value;
    }

    /**
     * The retry interval in seconds for dead servers listed in {@see setMasters()} and {@see setSlaves()}.
     *
     * @param int $value
     *
     * @return void
     */
    public function setServerRetryInterval(int $value): void
    {
        $this->serverRetryInterval = $value;
    }

    /**
     * Whether to shuffle {@see setMasters()} before getting one.
     *
     * @param bool $value
     *
     * @return void
     *
     * {@see setMasters()}
     */
    public function setShuffleMasters(bool $value): void
    {
        $this->shuffleMasters = $value;
    }

    /**
     * List of slave connection. Each DSN is used to create a slave DB connection. When {@see enableSlaves} is true,
     * one of these configurations will be chosen and used to create a DB connection for performing read queries only.
     *
     * @param string $key index slave connection.
     * @param array $config The configuration that should be merged with every slave configuration
     *
     * @return void
     *
     * For example,
     *
     * ```php
     * $connection->setSlaves(
     *     '1',
     *     [
     *         '__construct()' => ['mysql:host=127.0.0.1;dbname=yiitest;port=3306'],
     *         'setUsername()' => [$connection->getUsername()],
     *         'setPassword()' => [$connection->getPassword()]
     *     ]
     * );
     * ```
     *
     * {@see setEnableSlaves()}
     */
    public function setSlaves(string $key, array $config = []): void
    {
        $this->slaves[$key] = $config;
    }

    /**
     * The common prefix or suffix for table names. If a table name is given as `{{%TableName}}`, then the percentage
     * character `%` will be replaced with this property value. For example, `{{%post}}` becomes `{{tbl_post}}`.
     *
     * @param string $value
     *
     * @return void
     */
    public function setTablePrefix(string $value): void
    {
        $this->tablePrefix = $value;
    }

    /**
     * The username for establishing DB connection. Defaults to `null` meaning no username to use.
     *
     * @param string|null $value
     *
     * @return void
     */
    public function setUsername(?string $value): void
    {
        $this->username = $value;
    }

    /**
     * Executes callback provided in a transaction.
     *
     * @param callable $callback a valid PHP callback that performs the job. Accepts connection instance as parameter.
     * @param string|null $isolationLevel The isolation level to use for this transaction. {@see Transaction::begin()}
     * for details.
     *
     * @throws Throwable if there is any exception during query. In this case the transaction will be rolled back.
     *
     * @return mixed result of callback function
     */
    public function transaction(callable $callback, $isolationLevel = null)
    {
        $transaction = $this->beginTransaction($isolationLevel);

        $level = $transaction->getLevel();

        try {
            $result = $callback($this);

            if ($transaction->isActive() && $transaction->getLevel() === $level) {
                $transaction->commit();
            }
        } catch (Throwable $e) {
            $this->rollbackTransactionOnLevel($transaction, $level);

            throw $e;
        }

        return $result;
    }

    /**
     * Executes the provided callback by using the master connection.
     *
     * This method is provided so that you can temporarily force using the master connection to perform DB operations
     * even if they are read queries. For example,
     *
     * ```php
     * $result = $db->useMaster(function ($db) {
     *     return $db->createCommand('SELECT * FROM user LIMIT 1')->queryOne();
     * });
     * ```
     *
     * @param callable $callback a PHP callable to be executed by this method. Its signature is
     * `function (Connection $db)`. Its return value will be returned by this method.
     *
     * @throws Throwable if there is any exception thrown from the callback
     *
     * @return mixed the return value of the callback
     */
    public function useMaster(callable $callback)
    {
        if ($this->enableSlaves) {
            $this->enableSlaves = false;

            try {
                $result = $callback($this);
            } catch (Throwable $e) {
                $this->enableSlaves = true;

                throw $e;
            }
            $this->enableSlaves = true;
        } else {
            $result = $callback($this);
        }

        return $result;
    }

    private function getCacheKey(array $key): string
    {
        $jsonKey = json_encode($key);

        return md5($jsonKey);
    }
}
