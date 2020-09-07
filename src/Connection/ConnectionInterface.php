<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

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
     * Returns the name of the DB driver. Based on the the current {@see dsn}, in case it was not set explicitly by an
     * end user.
     *
     * @throws Exception
     * @throws InvalidConfigException
     *
     * @return string|null name of the DB driver
     */
    public function getDriverName(): ?string;

    /**
     * @var string the Data Source Name, or DSN, contains the information required to connect to the database.
     *
     * Please refer to the [PHP manual](https://secure.php.net/manual/en/pdo.construct.php) on the format of the DSN
     * string.
     *
     * For [SQLite](https://secure.php.net/manual/en/ref.pdo-sqlite.connection.php) you may use a [path alias](guide:concept-aliases)
     * for specifying the database path, e.g. `sqlite:@app/data/db.sql`.
     *
     * {@see charset}
     */
    public function getDsn(): ?string;

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
    public function getTableSchema($name, $refresh = false): ?TableSchema;

    /**
     * The charset used for database connection. The property is only used for MySQL, PostgreSQL databases.
     *
     * Defaults to null, meaning using default charset as configured by the database.
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
    public function setCharset(?string $value): void;

    /**
     * Whether to enable read/write splitting by using {@see setSlaves()} to read data. Note that if {@see setSlaves()}
     * is empty, read/write splitting will NOT be enabled no matter what value this property takes.
     *
     * @param bool $value
     *
     * @return void
     */
    public function setEnableSlaves(bool $value): void;
}
