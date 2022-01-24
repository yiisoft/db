<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use PDO;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\TableSchema;

interface ConnectionInterface
{
    /**
     * Creates a command for execution.
     *
     * @param string|null $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     *
     * @throws Exception
     * @throws InvalidConfigException
     *
     * @return Command the DB command
     */
    public function createCommand(?string $sql = null, array $params = []): Command;

    /**
     * Returns the name of the DB driver.
     *
     * @return string name of the DB driver
     */
    public function getDriverName(): string;

    /**
     * @return string the Data Source Name, or DSN, contains the information required to connect to the database.
     *
     * Please refer to the [PHP manual](https://secure.php.net/manual/en/pdo.construct.php) on the format of the DSN
     * string.
     *
     * For [SQLite](https://secure.php.net/manual/en/ref.pdo-sqlite.connection.php) you may use a
     * [path alias](guide:concept-aliases) for specifying the database path, e.g. `sqlite:@app/data/db.sql`.
     *
     * {@see charset}
     */
    public function getDsn(): string;

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     */
    public function getSchema(): Schema;

    /**
     * Returns a server version as a string comparable by {@see \version_compare()}.
     *
     * @return string server version as a string.
     */
    public function getServerVersion(): string;

    /**
     * Obtains the schema information for the named table.
     *
     * @param string $name table name.
     * @param bool $refresh whether to reload the table schema even if it is found in the cache.
     *
     * @return TableSchema|null
     */
    public function getTableSchema(string $name, $refresh = false): ?TableSchema;

    /**
     * Whether to enable read/write splitting by using {@see setSlaves()} to read data. Note that if {@see setSlaves()}
     * is empty, read/write splitting will NOT be enabled no matter what value this property takes.
     *
     * @param bool $value
     */
    public function setEnableSlaves(bool $value): void;

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
    public function quoteColumnName(string $name): string;

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
    public function quoteTableName(string $name): string;

    /**
     * Quotes a string value for use in a query.
     *
     * Note that if the parameter is not a string, it will be returned without change.
     *
     * @param int|string $value string to be quoted
     *
     * @throws Exception
     *
     * @return int|string the properly quoted string
     *
     * {@see http://php.net/manual/en/pdo.quote.php}
     */
    public function quoteValue($value);

    /**
     * Returns the PDO instance for the currently active slave connection.
     *
     * When {@see enableSlaves} is true, one of the slaves will be used for read queries, and its PDO instance will be
     * returned by this method.
     *
     * @param bool $fallbackToMaster whether to return a master PDO in case none of the slave connections is available.
     *
     * @throws Exception
     *
     * @return PDO the PDO instance for the currently active slave connection. `null` is returned if no slave connection
     * is available and `$fallbackToMaster` is false.
     */
    public function getSlavePdo(bool $fallbackToMaster = true): ?PDO;
}
