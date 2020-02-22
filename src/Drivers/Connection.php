<?php

declare(strict_types=1);

namespace Yiisoft\Db\Drivers;

use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Db\Commands\Command;
use Yiisoft\Db\Exceptions\Exception;
use Yiisoft\Db\Exceptions\InvalidCallException;
use Yiisoft\Db\Exceptions\InvalidConfigException;
use Yiisoft\Db\Exceptions\NotSupportedException;
use Yiisoft\Db\Factory\DatabaseFactory;
use Yiisoft\Db\Querys\QueryBuilder;
use Yiisoft\Db\Schemas\Schema;
use Yiisoft\Db\Schemas\TableSchema;
use Yiisoft\Db\Transactions\Transaction;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Profiler\Profiler;

/**
 * Connection represents a connection to a database via [PDO](http://php.net/manual/en/book.pdo.php).
 *
 * Connection works together with {@see Command}, {@see DataReader} and {@see Transaction} to provide data access to
 * various DBMS in a common set of APIs. They are a thin wrapper of the
 * [PDO PHP extension](http://php.net/manual/en/book.pdo.php).
 *
 * Connection supports database replication and read-write splitting. In particular, a Connection component can be
 * configured with multiple {@see masters} and {@see slaves}. It will do load balancing and failover by choosing
 * appropriate servers. It will also automatically direct read operations to the slaves and write operations to
 * the masters.
 *
 * To establish a DB connection, set {@see dsn}, {@see username} and {@see password}, and then call {@see open()}
 * to connect to the database server. The current state of the connection can be checked using {@see $isActive}.
 *
 * The following example shows how to create a Connection instance and establish
 * the DB connection:
 *
 * ```php
 * $connection = new \\Yiisoft\Db\Drivers\Connection([
 *     'dsn' => $dsn,
 *     'username' => $username,
 *     'password' => $password,
 * ]);
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
 * When the parameters are coming from user input, you should use this approach
 * to prevent SQL injection attacks. The following is an example:
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post WHERE id=:id');
 * $command->bindValue(':id', $_GET['id']);
 * $post = $command->query();
 * ```
 *
 * For more information about how to perform various DB queries, please refer to [[Command]].
 *
 * If the underlying DBMS supports transactions, you can perform transactional SQL queries
 * like the following:
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
 * Connection is often used as an application component and configured in the application
 * configuration like the following:
 *
 * ```php
 * 'components' => [
 *     'db' => [
 *         '__class' => \\Yiisoft\Db\Drivers::class,
 *         'dsn' => 'mysql:host=127.0.0.1;dbname=demo;charset=utf8',
 *         'username' => 'root',
 *         'password' => '',
 *     ],
 * ],
 * ```
 *
 * The {@see dsn} property can be defined via configuration array:
 *
 * ```php
 * 'components' => [
 *     'db' => [
 *         '__class' => \\Yiisoft\Db\Drivers::class,
 *         'dsn' => [
 *             'driver' => 'mysql',
 *             'host' => '127.0.0.1',
 *             'dbname' => 'demo',
 *             'charset' => 'utf8',
 *          ],
 *         'username' => 'root',
 *         'password' => '',
 *     ],
 * ],
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
class Connection
{
    private ?string $dsn = null;

    /**
     * @var LoggerInterface $logger
     */
    private ?LoggerInterface $logger = null;

    /**
     * @var string the username for establishing DB connection. Defaults to `null` meaning no username to use.
     */
    private ?string $username = null;

    /**
     * @var string the password for establishing DB connection. Defaults to `null` meaning no password to use.
     */
    private ?string $password = null;

    /**
     * @var array PDO attributes (name => value) that should be set when calling {@see open()} to establish a DB
     * connection. Please refer to the [PHP manual](http://php.net/manual/en/pdo.setattribute.php) for details about
     * available attributes.
     */
    private array $attributes = [];

    /**
     * @var PDO the PHP PDO instance associated with this DB connection. This property is mainly managed by
     * {@see open()} and {@see close()} methods. When a DB connection is active, this property will represent a PDO
     * instance; otherwise, it will be null.
     *
     * @see pdoClass
     */
    private ?PDO $pdo = null;

    /**
     * @var bool whether to enable schema caching. Note that in order to enable truly schema caching, a valid cache
     * component as specified by {@see schemaCache} must be enabled and {@see enableSchemaCache} must be set true.
     *
     * @see schemaCacheDuration
     * @see schemaCacheExclude
     * @see schemaCache
     */
    private bool $enableSchemaCache = true;

    /**
     * @var int number of seconds that table metadata can remain valid in cache. Use 0 to indicate that the cached data
     * will never expire.
     *
     * @see enableSchemaCache
     */
    private int $schemaCacheDuration = 3600;

    /**
     * @var array list of tables whose metadata should NOT be cached. Defaults to empty array. The table names may
     * contain schema prefix, if any. Do not quote the table names.
     *
     * @see enableSchemaCache
     */
    private array $schemaCacheExclude = [];

    /**
     * @var CacheInterface|null the cache object or the ID of the cache application component that is used to cache
     * the table metadata.
     *
     * {@see enableSchemaCache}
     */
    private ?CacheInterface $schemaCache = null;

    /**
     * @var bool whether to enable query caching. Note that in order to enable query caching, a valid cache component as
     * specified by {@see queryCache} must be enabled and {@see enableQueryCache} must be set true. Also, only the
     * results of the queries enclosed within {@see cache()} will be cached.
     *
     * {@see queryCache}
     * {@see cache()}
     * {@see noCache()}
     */
    private bool $enableQueryCache = true;

    /**
     * @var int the default number of seconds that query results can remain valid in cache. Defaults to 3600, meaning
     * 3600 seconds, or one hour. Use 0 to indicate that the cached data will never expire. The value of this property
     * will be used when {@see cache()} is called without a cache duration.
     *
     * {@see enableQueryCache}
     * {@see cache()}
     */
    private int $queryCacheDuration = 3600;

    /**
     * @var CacheInterface the cache object or the ID of the cache application component that is used for query
     * caching.
     *
     * @see enableQueryCache
     */
    private CacheInterface $queryCache;

    /**
     * @var string|null the charset used for database connection. The property is only used for MySQL, PostgreSQL and
     * CUBRID databases. Defaults to null, meaning using default charset as configured by the database.
     *
     * For Oracle Database, the charset must be specified in the {@see dsn}, for example for UTF-8 by appending
     * `;charset=UTF-8` to the DSN string.
     *
     * The same applies for if you're using GBK or BIG5 charset with MySQL, then it's highly recommended to specify
     * charset via {@see dsn} like `'mysql:dbname=mydatabase;host=127.0.0.1;charset=GBK;'`.
     */
    private ?string $charset = null;

    /**
     * @var bool whether to turn on prepare emulation. Defaults to false, meaning PDO will use the native prepare
     * support if available. For some databases (such as MySQL), this may need to be set true so that PDO can emulate
     * the prepare support to bypass the buggy native prepare support. The default value is null, which means the PDO
     * ATTR_EMULATE_PREPARES value will not be changed.
     */
    private ?bool $emulatePrepare = null;

    /**
     * @var string the common prefix or suffix for table names. If a table name is given as `{{%TableName}}`, then the
     * percentage character `%` will be replaced with this property value. For example, `{{%post}}` becomes
     * `{{tbl_post}}`.
     */
    private string $tablePrefix = '';

    /**
     * @var array mapping between PDO driver names and {@see Schema} classes. The keys of the array are PDO driver names
     * while the values are either the corresponding schema class names or configurations.
     *
     * This property is mainly used by {@see getSchema()} when fetching the database schema information. You normally do
     * not need to set this property unless you want to use your own {@see Schema} class to support DBMS that is not
     * supported by Yii.
     */
    private array $schemaMap = [
        'pgsql' => \Yiisoft\Db\Pgsql\Schema::class, // PostgreSQL
        'mysqli' => \Yiisoft\Db\Mysql\Schema::class, // MySQL
        'mysql' => \Yiisoft\Db\Mysql\Schema::class, // MySQL
        'sqlite' => \Yiisoft\Db\Sqlite\Schema::class, // sqlite 3
        'sqlite2' => \Yiisoft\Db\Sqlite\Schema::class, // sqlite 2
        'sqlsrv' => \Yiisoft\Db\Mssql\Schema::class, // newer MSSQL driver on MS Windows hosts
        'oci' => \Yiisoft\Db\Oci\Schema::class, // Oracle driver
        'mssql' => \Yiisoft\Db\Mssql\Schema::class, // older MSSQL driver on MS Windows hosts
        'dblib' => \Yiisoft\Db\Mssql\Schema::class, // dblib drivers on GNU/Linux (and maybe other OSes) hosts
    ];

    /**
     * @var string Custom PDO wrapper class. If not set, it will use {@see PDO} or {@see \Yiisoft\Db\mssql\PDO}
     * when MSSQL is used.
     *
     * @see pdo
     */
    private ?string $pdoClass = null;

    /**
     * @var array mapping between PDO driver names and {@see Command} classes. The keys of the array are PDO driver
     * names while the values are either the corresponding command class names or configurations.
     *
     * This property is mainly used by {@see createCommand()} to create new database {@see Command} objects. You
     * normally do not need to set this property unless you want to use your own {@see Command} class or support
     * DBMS that is not supported by Yii.
     */
    private array $commandMap = [
        'pgsql'   => Command::class, // PostgreSQL
        'mysqli'  => Command::class, // MySQL
        'mysql'   => Command::class, // MySQL
        'mariadb' => Command::class, // MySQL
        'sqlite'  => \Yiisoft\Db\Sqlite\Command::class, // sqlite 3
        'sqlite2' => \Yiisoft\Db\Sqlite\Command::class, // sqlite 2
        'sqlsrv'  => Command::class, // newer MSSQL driver on MS Windows hosts
        'oci'     => Command::class, // Oracle driver
        'mssql'   => Command::class, // older MSSQL driver on MS Windows hosts
        'dblib'   => Command::class, // dblib drivers on GNU/Linux (and maybe other OSes) hosts
    ];

    /**
     * @var bool whether to enable [savepoint](http://en.wikipedia.org/wiki/Savepoint). Note that if the underlying
     * DBMS does not support savepoint, setting this property to be true will have no effect.
     */
    private bool $enableSavepoint = true;

    /**
     * @var int the retry interval in seconds for dead servers listed in {@see masters} and {@see slaves}. This is used
     * together with {@see serverStatusCache}.
     */
    public int $serverRetryInterval = 600;

    /**
     * @var bool whether to enable read/write splitting by using {@see slaves} to read data. Note that if {@see slaves}
     * is empty, read/write splitting will NOT be enabled no matter what value this property takes.
     */
    public bool $enableSlaves = true;

    /**
     * @var []Connection list of slave connection configurations. Each configuration is used to create a slave DB connection.
     * When {@see enableSlaves} is true, one of these configurations will be chosen and used to create a DB connection
     * for performing read queries only.
     *
     * {@see enableSlaves}
     * {@see slaveConfig}
     */
    public array $slaves = [];

    /**
     * @var array the configuration that should be merged with every slave configuration listed in {@see slaves}.
     *
     * For example,
     *
     * ```php
     * [
     *     'username' => 'slave',
     *     'password' => 'slave',
     *     'attributes' => [
     *         // use a smaller connection timeout
     *         PDO::ATTR_TIMEOUT => 10,
     *     ],
     * ]
     * ```
     */
    public array $slaveConfig = [];

    /**
     * @var array list of master connection configurations. Each configuration is used to create a master DB connection.
     * When {@see open()} is called, one of these configurations will be chosen and used to create a DB connection which
     * will be used by this object. Note that when this property is not empty, the connection setting (e.g. "dsn",
     * "username") of this object will be ignored.
     *
     * {@see masterConfig}
     * {@see shuffleMasters}
     */
    public array $masters = [];

    /**
     * @var array the configuration that should be merged with every master configuration listed in {@see masters}.
     *
     * For example,
     *
     * ```php
     * [
     *     'username' => 'master',
     *     'password' => 'master',
     *     'attributes' => [
     *         // use a smaller connection timeout
     *         PDO::ATTR_TIMEOUT => 10,
     *     ],
     * ]
     * ```
     */
    public array $masterConfig = [];

    /**
     * @var bool whether to shuffle {@see masters} before getting one.
     * {@see masters}
     */
    public bool $shuffleMasters = true;

    /**
     * @var bool whether to enable logging of database queries. Defaults to true. You may want to disable this option in
     * a production environment to gain performance if you do not need the information being logged.
     *
     * {@see enableProfiling}
     */
    public bool $enableLogging = true;

    /**
     * @var bool whether to enable profiling of opening database connection and database queries. Defaults to true. You
     * may want to disable this option in a production environment to gain performance if you do not need the
     * information being logged.
     *
     * {@see enableLogging}
     */
    public bool $enableProfiling = true;

    /**
     * @var Transaction|null the currently active transaction
     */
    private ?Transaction $transaction = null;

    /**
     * @var Schema|null the database schema
     */
    private ?Schema $schema = null;

    /**
     * @var string|null driver name
     */
    private ?string $driverName = null;

    /**
     * @var Connection|null the currently active master connection
     */
    private ?Connection $master = null;

    /**
     * @var Connection|null the currently active slave connection
     */
    private ?Connection $slave = null;

    /**
     * @var array query cache parameters for the {cache()} calls
     */
    private array $queryCacheInfo = [];

    /**
     * @var Profiler $profiler
     */
    private Profiler $profiler;

    /**
     * @var string[] quoted table name cache for {@see quoteTableName()} calls
     */
    private array $quotedTableNames = [];

    /**
     * @var string[] quoted column name cache for {@see quoteColumnName()} calls
     */
    private array $quotedColumnNames = [];

    /**
     * Constructor.
     *
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param Profiler $profiler
     * @param array dns connection
     *
     * @throws InvalidConfigException
     */
    public function __construct(?CacheInterface $cache, LoggerInterface $logger, Profiler $profiler, string $dsn)
    {
        $this->dsn = $dsn;
        $this->schemaCache = $cache;
        $this->logger = $logger;
        $this->profiler = $profiler;
    }

    /**
     * Returns a value indicating whether the DB connection is established.
     *
     * @return bool whether the DB connection is established
     */
    public function getIsActive(): bool
    {
        return $this->pdo !== null;
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
     * @param int $duration the number of seconds that query results can remain valid in the cache. If this is not set,
     * the value of {@see queryCacheDuration} will be used instead. Use 0 to indicate that the cached data will never
     * expire.
     * @param Dependency $dependency the cache dependency associated with the cached query
     * results.
     *
     * @return mixed the return result of the callable
     *
     * @throws \Throwable if there is any exception during query
     *
     * {@see enableQueryCache}
     * {@see queryCache}
     * {@see noCache()}
     */
    public function cache(callable $callable, $duration = null, $dependency = null)
    {
        $this->queryCacheInfo[] = [$duration ?? $this->queryCacheDuration, $dependency];

        try {
            $result = $callable($this);

            array_pop($this->queryCacheInfo);

            return $result;
        } catch (\Throwable $e) {
            array_pop($this->queryCacheInfo);

            throw $e;
        }
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
     * @return mixed the return result of the callable
     *
     * @throws \Throwable if there is any exception during query
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
        } catch (\Throwable $e) {
            array_pop($this->queryCacheInfo);

            throw $e;
        }
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
    public function getQueryCacheInfo(?int $duration, ?Dependency $dependency): ?array
    {
        $result = null;

        if ($this->enableQueryCache) {
            $info = \end($this->queryCacheInfo);

            if (\is_array($info)) {
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

    /**
     * Establishes a DB connection.
     *
     * It does nothing if a DB connection has already been established.
     *
     * @throws Exception if connection fails
     * @throws InvalidArgumentException
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
        $enableProfiling = $this->enableProfiling;

        try {
            $this->logger->log(LogLevel::INFO, $token);

            if ($enableProfiling) {
                $this->profiler->begin($token, [__METHOD__]);
            }

            $this->pdo = $this->createPdoInstance();

            $this->initConnection();

            if ($enableProfiling) {
                $this->profiler->end($token, [__METHOD__]);
            }
        } catch (\PDOException $e) {
            if ($enableProfiling) {
                $this->logger->log(LogLevel::ERROR, $token);
                $this->profiler->end($token, [__METHOD__]);
            }

            throw new Exception($e->getMessage(), $e->errorInfo, (int) $e->getCode(), $e);
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
            $this->logger->log(LogLevel::DEBUG, 'Closing DB connection: ' . $this->dsn . ' ' . __METHOD__);

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
     * Creates the PDO instance.
     *
     * This method is called by {@see open} to establish a DB connection. The default implementation will create a PHP
     * PDO instance. You may override this method if the default PDO needs to be adapted for certain DBMS.
     *
     * @return PDO the pdo instance
     */
    protected function createPdoInstance(): PDO
    {
        $pdoClass = $this->pdoClass;

        if ($pdoClass === null) {
            $pdoClass = 'PDO';

            if ($this->driverName !== null) {
                $driver = $this->driverName;
            } elseif (($pos = strpos($this->dsn, ':')) !== false) {
                $driver = strtolower(substr($this->dsn, 0, $pos));
            }

            if (isset($driver)) {
                if ($driver === 'mssql' || $driver === 'dblib') {
                    $pdoClass = mssql\PDO::class;
                } elseif ($driver === 'sqlsrv') {
                    $pdoClass = mssql\SqlsrvPDO::class;
                }
            }
        }

        $dsn = $this->dsn;

        if (strncmp('sqlite:@', $dsn, 8) === 0) {
            $dsn = 'sqlite:' . substr($dsn, 7);
        }

        return new $pdoClass($dsn, $this->username, $this->password, $this->attributes);
    }

    /**
     * Initializes the DB connection.
     *
     * This method is invoked right after the DB connection is established. The default implementation turns on
     * `PDO::ATTR_EMULATE_PREPARES` if {@see emulatePrepare} is true. It then triggers an {@see EVENT_AFTER_OPEN} event.
     */
    protected function initConnection(): void
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($this->emulatePrepare !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->emulatePrepare);
        }

        if ($this->charset !== null && in_array($this->getDriverName(), ['pgsql', 'mysql', 'mysqli', 'cubrid', 'mariadb'], true)) {
            $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
        }

        //$this->trigger(self::EVENT_AFTER_OPEN);
    }

    /**
     * Creates a command for execution.
     *
     * @param string $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     *
     * @return Command the DB command
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     *
     */
    public function createCommand($sql = null, $params = []): Command
    {
        $driver = $this->getDriverName();

        if ($sql !== null) {
            $sql = $this->quoteSql($sql);
        }

        /** @var Command $command */
        $command = new $this->commandMap[$driver]($this->profiler, $this->logger, $this, $sql);

        return $command->bindValues($params);
    }

    /**
     * Returns the currently active transaction.
     *
     * @return Transaction|null the currently active transaction. Null if no active transaction.
     */
    public function getTransaction(): ?Transaction
    {
        return $this->transaction && $this->transaction->getIsActive() ? $this->transaction : null;
    }

    /**
     * Starts a transaction.
     *
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     *
     * {@see Transaction::begin()} for details.
     *
     * @return Transaction the transaction initiated
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws InvalidArgumentException
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
     * Executes callback provided in a transaction.
     *
     * @param callable $callback a valid PHP callback that performs the job. Accepts connection instance as parameter.
     * @param string|null $isolationLevel The isolation level to use for this transaction. {@see Transaction::begin()}
     * for details.
     *
     * @return mixed result of callback function
     *
     * @throws \Throwable if there is any exception during query. In this case the transaction will be rolled back.
     * @throws InvalidArgumentException
     *
     */
    public function transaction(callable $callback, $isolationLevel = null)
    {
        $transaction = $this->beginTransaction($isolationLevel);

        $level = $transaction->getLevel();

        try {
            $result = $callback($this);

            if ($transaction->getIsActive() && $transaction->getLevel() === $level) {
                $transaction->commit();
            }
        } catch (\Throwable $e) {
            $this->rollbackTransactionOnLevel($transaction, $level);

            throw $e;
        }

        return $result;
    }

    /**
     * Rolls back given {@see Transaction} object if it's still active and level match. In some cases rollback can fail,
     * so this method is fail safe. Exceptions thrown from rollback will be caught and just logged with
     * {@see logger->log()}.
     *
     * @param Transaction $transaction Transaction object given from {@see beginTransaction()}.
     * @param int $level Transaction level just after {@see beginTransaction()} call.
     */
    private function rollbackTransactionOnLevel(Transaction $transaction, int $level): void
    {
        if ($transaction->getIsActive() && $transaction->getLevel() === $level) {
            /* https://github.com/yiisoft/yii2/pull/13347 */
            try {
                $transaction->rollBack();
            } catch (Exception $e) {
                $this->logger->log(LogLevel::ERROR, $e, [__METHOD__]);
                /* hide this exception to be able to continue throwing original exception outside */
            }
        }
    }

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException if there is no support for the current driver type
     * @throws InvalidArgumentException
     */
    public function getSchema(): Schema
    {
        if ($this->schema !== null) {
            return $this->schema;
        }

        $driver = $this->getDriverName();

        if (isset($this->schemaMap[$driver])) {
            $class = $this->schemaMap[$driver];

            return $this->schema = new $class($this);
        }

        throw new NotSupportedException("Connection does not support reading schema information for '$driver' DBMS.");
    }

    /**
     * Returns the query builder for the current DB connection.
     *
     * @return QueryBuilder the query builder for the current DB connection.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws InvalidArgumentException
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getSchema()->getQueryBuilder();
    }

    /**
     * Can be used to set {@see QueryBuilder} configuration via Connection configuration array.
     *
     * @param iterable $config the {@see QueryBuilder} properties to be configured.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function setQueryBuilder(iterable $config): void
    {
        $builder = $this->getQueryBuilder();

        foreach ($config as $key => $value) {
            $builder->{$key} = $value;
        }
    }

    /**
     * Obtains the schema information for the named table.
     *
     * @param string $name table name.
     * @param bool $refresh whether to reload the table schema even if it is found in the cache.
     *
     * @return TableSchema
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function getTableSchema($name, $refresh = false): ?TableSchema
    {
        return $this->getSchema()->getTableSchema($name, $refresh);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     *
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * {@see http://php.net/manual/en/pdo.lastinsertid.php'>http://php.net/manual/en/pdo.lastinsertid.php}
     */
    public function getLastInsertID($sequenceName = ''): string
    {
        return $this->getSchema()->getLastInsertID($sequenceName);
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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws InvalidArgumentException
     *
     * {@see http://php.net/manual/en/pdo.quote.php}
     */
    public function quoteValue($value)
    {
        return $this->getSchema()->quoteValue($value);
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
            '/(\\{\\{(%?[\w\-\. ]+%?)}}|\\[\\[([\w\-\. ]+)]])/',
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
     * Returns the name of the DB driver. Based on the the current {@see dsn}, in case it was not set explicitly
     * by an end user.
     *
     * @return string name of the DB driver
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     */
    public function getDriverName(): string
    {
        if ($this->driverName === null) {
            if (($pos = strpos($this->dsn, ':')) !== false) {
                $this->driverName = strtolower(substr($this->dsn, 0, $pos));
            } else {
                $this->driverName = strtolower($this->getSlavePdo()->getAttribute(PDO::ATTR_DRIVER_NAME));
            }
        }

        return $this->driverName;
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
     * Returns a server version as a string comparable by {@see \version_compare()}.
     *
     * @return string server version as a string.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws InvalidArgumentException
     */
    public function getServerVersion(): string
    {
        return $this->getSchema()->getServerVersion();
    }

    /**
     * Returns the PDO instance for the currently active slave connection.
     *
     * When {@see enableSlaves} is true, one of the slaves will be used for read queries, and its PDO instance will be
     * returned by this method.
     *
     * @param bool $fallbackToMaster whether to return a master PDO in case none of the slave connections is available.
     *
     * @return PDO the PDO instance for the currently active slave connection. `null` is returned if no slave connection
     * is available and `$fallbackToMaster` is false.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     */
    public function getSlavePdo(bool $fallbackToMaster = true): PDO
    {
        $db = $this->getSlave(false);

        if ($db === null) {
            return $fallbackToMaster ? $this->getMasterPdo() : null;
        }

        return $db->pdo;
    }

    /**
     * Returns the PDO instance for the currently active master connection.
     *
     * This method will open the master DB connection and then return {@see pdo}.
     *
     * @return PDO the PDO instance for the currently active master connection.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getMasterPdo(): PDO
    {
        $this->open();

        return $this->pdo;
    }

    /**
     * Returns the currently active slave connection.
     *
     * If this method is called for the first time, it will try to open a slave connection when {@see enableSlaves}
     * is true.
     *
     * @param bool $fallbackToMaster whether to return a master connection in case there is no slave connection
     * available.
     *
     * @return Connection the currently active slave connection. `null` is returned if there is no slave available and
     * `$fallbackToMaster` is false.
     *
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function getSlave(bool $fallbackToMaster = true)
    {
        if (!$this->enableSlaves) {
            return $fallbackToMaster ? $this : null;
        }

        if ($this->slave === null) {
            $this->slave = $this->openFromPool($this->slaves, $this->slaveConfig);
        }

        return $this->slave === null && $fallbackToMaster ? $this : $this->slave;
    }

    /**
     * Returns the currently active master connection.
     *
     * If this method is called for the first time, it will try to open a master connection.
     *
     * @return Connection the currently active master connection. `null` is returned if there is no master available.
     *
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     */
    public function getMaster(): ?Connection
    {
        if ($this->master === null) {
            $this->master = $this->shuffleMasters
                ? $this->openFromPool($this->masters, $this->masterConfig)
                : $this->openFromPoolSequentially($this->masters, $this->masterConfig);
        }

        return $this->master;
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
     * @throws \Throwable if there is any exception thrown from the callback
     *
     * @return mixed the return value of the callback
     */
    public function useMaster(callable $callback)
    {
        if ($this->enableSlaves) {
            $this->enableSlaves = false;

            try {
                $result = $callback($this);
            } catch (\Throwable $e) {
                $this->enableSlaves = true;

                throw $e;
            }
            $this->enableSlaves = true;
        } else {
            $result = $callback($this);
        }

        return $result;
    }

    /**
     * Opens the connection to a server in the pool.
     *
     * This method implements the load balancing among the given list of the servers.
     *
     * Connections will be tried in random order.
     *
     * @param array $pool the list of connection configurations in the server pool
     * @param array $sharedConfig the configuration common to those given in `$pool`.
     *
     * @return Connection the opened DB connection, or `null` if no server is available
     *
     * @throws InvalidConfigException if a configuration does not specify "dsn"
     * @throws InvalidArgumentException
     */
    protected function openFromPool(array $pool, array $sharedConfig): ?Connection
    {
        shuffle($pool);

        return $this->openFromPoolSequentially($pool, $sharedConfig);
    }

    /**
     * Opens the connection to a server in the pool.
     *
     * This method implements the load balancing among the given list of the servers.
     *
     * Connections will be tried in sequential order.
     *
     * @param array $pool the list of connection configurations in the server pool
     * @param array $sharedConfig the configuration common to those given in `$pool`.
     *
     * @return Connection the opened DB connection, or `null` if no server is available
     *
     * @throws InvalidConfigException if a configuration does not specify "dsn"
     * @throws InvalidArgumentException
     */
    protected function openFromPoolSequentially(array $pool, array $sharedConfig): ?Connection
    {
        if (!$pool) {
            return null;
        }

        if (!isset($sharedConfig['__class'])) {
            $sharedConfig['__class'] = get_class($this);
        }

        foreach ($pool as $config) {
            $config = array_merge($sharedConfig, $config);

            if (empty($config['dsn'])) {
                throw new InvalidConfigException('The "dsn" option must be specified.');
            }

            $dsn = $config['dsn'];

            unset($config['dsn']);

            $key = [__METHOD__, $dsn];

            $config = array_merge(
                [
                    '__construct()' => [
                        $this->schemaCache,
                        $this->logger,
                        $this->profiler,
                        $dsn
                    ]
                ],
                $config
            );

            /* @var $db Connection */
            $db = DatabaseFactory::createClass($config);

            if ($this->schemaCache instanceof CacheInterface && $this->schemaCache->get($key)) {
                // should not try this dead server now
                continue;
            }

            try {
                $db->open();

                return $db;
            } catch (Exception $e) {
                $this->logger->log(
                    LogLevel::WARNING,
                    "Connection ({$dsn}) failed: " . $e->getMessage() . ' ' . __METHOD__
                );

                if ($this->schemaCache instanceof CacheInterface) {
                    // mark this server as dead and only retry it after the specified interval
                    $this->schemaCache->set($key, 1, $this->serverRetryInterval);
                }

                return null;
            }
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
     * Reset the connection after cloning.
     */
    public function __clone()
    {
        $this->master = null;
        $this->slave = null;
        $this->schema = null;
        $this->transaction = null;

        if (strncmp($this->dsn, 'sqlite::memory:', 15) !== 0) {
            /* reset PDO connection, unless its sqlite in-memory, which can only have one connection */
            $this->pdo = null;
        }
    }

    public function getDsn(): ?string
    {
        return $this->dsn;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPDO(): ?PDO
    {
        return $this->pdo;
    }

    public function setUsername(?string $value): void
    {
        $this->username = $value;
    }

    public function setPassword(?string $value): void
    {
        $this->password = $value;
    }

    public function getEnableSchemaCache(): bool
    {
        return $this->enableSchemaCache;
    }

    public function setEnableSchemaCache(bool $value): void
    {
        $this->enableSchemaCache = $value;
    }

    public function getSchemaCacheExclude(): array
    {
        return $this->schemaCacheExclude;
    }

    public function setSchemaCacheExclude(array $value): void
    {
        $this->schemaCacheExclude = $value;
    }

    public function getSchemaCache(): CacheInterface
    {
        return $this->schemaCache;
    }

    public function setSchemaCache(?CacheInterface $value): void
    {
        $this->schemaCache = $value;
    }

    public function getSchemaCacheDuration(): int
    {
        return $this->schemaCacheDuration;
    }

    public function getEnableQueryCache(): bool
    {
        return $this->enableQueryCache;
    }

    public function setEnableQueryCache(bool $value): void
    {
        $this->enableQueryCache = $value;
    }

    public function setQueryCache(CacheInterface $value): void
    {
        $this->queryCache = $value;
    }

    public function getQueryCacheDuration(): ?int
    {
        return $this->queryCacheDuration;
    }

    public function setEmulatePrepare(bool $value): void
    {
        $this->emulatePrepare = $value;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix(string $value): void
    {
        $this->tablePrefix = $value;
    }

    public function getEnableSavepoint(): bool
    {
        return $this->enableSavepoint;
    }

    public function setEnableSavepoint(bool $value): void
    {
        $this->enableSavepoint = $value;
    }

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains special characters including '(', '[[' and '{{',
     * then this method will do nothing.
     *
     * @param string $name table name
     *
     * @return string the properly quoted table name
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws InvalidArgumentException
     */
    public function quoteTableName(string $name): string
    {
        if (isset($this->quotedTableNames[$name])) {
            return $this->quotedTableNames[$name];
        }

        return $this->quotedTableNames[$name] = $this->getSchema()->quoteTableName($name);
    }

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains special characters including '(', '[[' and '{{',
     * then this method will do nothing.
     *
     * @param string $name column name
     *
     * @return string the properly quoted column name
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws InvalidArgumentException
     */
    public function quoteColumnName(string $name): string
    {
        if (isset($this->quotedColumnNames[$name])) {
            return $this->quotedColumnNames[$name];
        }

        return $this->quotedColumnNames[$name] = $this->getSchema()->quoteColumnName($name);
    }
}
