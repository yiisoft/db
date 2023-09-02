<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_key_exists;
use function array_values;

final class ColumnTypes
{
    public function __construct(private PdoConnectionInterface $db)
    {
    }

    /**
     * This is not used as a dataprovider for testGetColumnType to speed up the test when used as dataprovider every
     * single line will cause a reconnect with the database which is not needed here.
     */
    public function getColumnTypes(): array
    {
        $items = [
            '$this->bigInteger()' => [
                SchemaInterface::TYPE_BIGINT,
                [
                    'mysql' => 'bigint(20)',
                    'pgsql' => 'bigint',
                    'sqlite' => 'bigint',
                    'oci' => 'NUMBER(20)',
                    'sqlsrv' => 'bigint',
                ],
            ],
            '$this->bigInteger()->notNull()' => [
                SchemaInterface::TYPE_BIGINT . ' NOT NULL',
                [
                    'mysql' => 'bigint(20) NOT NULL',
                    'pgsql' => 'bigint NOT NULL',
                    'sqlite' => 'bigint NOT NULL',
                    'oci' => 'NUMBER(20) NOT NULL',
                    'sqlsrv' => 'bigint NOT NULL',
                ],
            ],
            '$this->bigInteger()->check(\'value > 5\')' => [
                SchemaInterface::TYPE_BIGINT . ' CHECK (value > 5)',
                [
                    'mysql' => 'bigint(20) CHECK (value > 5)',
                    'pgsql' => 'bigint CHECK (value > 5)',
                    'sqlite' => 'bigint CHECK (value > 5)',
                    'oci' => 'NUMBER(20) CHECK (value > 5)',
                    'sqlsrv' => 'bigint CHECK (value > 5)',
                ],
            ],
            '$this->bigInteger(8)' => [
                SchemaInterface::TYPE_BIGINT . '(8)',
                [
                    'mysql' => 'bigint(8)',
                    'pgsql' => 'bigint',
                    'sqlite' => 'bigint',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'bigint',
                ],
            ],
            '$this->bigInteger(8)->check(\'value > 5\')' => [
                SchemaInterface::TYPE_BIGINT . '(8) CHECK (value > 5)',
                [
                    'mysql' => 'bigint(8) CHECK (value > 5)',
                    'pgsql' => 'bigint CHECK (value > 5)',
                    'sqlite' => 'bigint CHECK (value > 5)',
                    'oci' => 'NUMBER(8) CHECK (value > 5)',
                    'sqlsrv' => 'bigint CHECK (value > 5)',
                ],
            ],
            '$this->bigPrimaryKey()' => [
                SchemaInterface::TYPE_BIGPK,
                [
                    'mysql' => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            '$this->binary()' => [
                SchemaInterface::TYPE_BINARY,
                [
                    'mysql' => 'blob',
                    'pgsql' => 'bytea',
                    'sqlite' => 'blob',
                    'oci' => 'BLOB',
                    'sqlsrv' => 'varbinary(max)',
                ],
            ],
            '$this->boolean()->notNull()->defaultValue(1)' => [
                SchemaInterface::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
                [
                    'mysql' => 'bit(1) NOT NULL DEFAULT 1',
                    'sqlite' => 'boolean NOT NULL DEFAULT 1',
                    'sqlsrv' => 'bit NOT NULL DEFAULT 1',
                ],
            ],
            '$this->boolean()->notNull()->defaultValue(true)' => [
                SchemaInterface::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
                [
                    'pgsql' => 'boolean NOT NULL DEFAULT TRUE',
                ],
            ],
            '$this->boolean()' => [
                SchemaInterface::TYPE_BOOLEAN,
                [
                    'mysql' => 'bit(1)',
                    'pgsql' => 'boolean',
                    'sqlite' => 'boolean',
                    'oci' => 'NUMBER(1)',
                    'sqlsrv' => 'bit',
                ],
            ],
            '$this->char()->check(\'value LIKE \\\'test%\\\'\')' => [
                SchemaInterface::TYPE_CHAR . ' CHECK (value LIKE \'test%\')',
                [
                    'pgsql' => 'char(1) CHECK (value LIKE \'test%\')',
                    'mysql' => 'char(1) CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->char()->check(\'value LIKE "test%"\')' => [
                SchemaInterface::TYPE_CHAR . ' CHECK (value LIKE "test%")',
                [
                    'sqlite' => 'char(1) CHECK (value LIKE "test%")',
                ],
            ],
            '$this->char()->notNull()' => [
                SchemaInterface::TYPE_CHAR . ' NOT NULL',
                [
                    'mysql' => 'char(1) NOT NULL',
                    'pgsql' => 'char(1) NOT NULL',
                    'sqlite' => 'char(1) NOT NULL',
                    'oci' => 'CHAR(1) NOT NULL',
                ],
            ],
            '$this->char(6)->check(\'value LIKE "test%"\')' => [
                SchemaInterface::TYPE_CHAR . '(6) CHECK (value LIKE "test%")',
                [
                    'sqlite' => 'char(6) CHECK (value LIKE "test%")',
                ],
            ],
            '$this->char(6)->check(\'value LIKE \\\'test%\\\'\')' => [
                SchemaInterface::TYPE_CHAR . '(6) CHECK (value LIKE \'test%\')',
                [
                    'mysql' => 'char(6) CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->char(6)->unsigned()' => [
                SchemaInterface::TYPE_CHAR . '(6)',
                [
                    'pgsql' => 'char(6)',
                ],
            ],
            '$this->char(6)' => [
                SchemaInterface::TYPE_CHAR . '(6)',
                [
                    'mysql' => 'char(6)',
                    'pgsql' => 'char(6)',
                    'sqlite' => 'char(6)',
                    'oci' => 'CHAR(6)',
                ],
            ],
            '$this->char()' => [
                SchemaInterface::TYPE_CHAR,
                [
                    'mysql' => 'char(1)',
                    'pgsql' => 'char(1)',
                    'sqlite' => 'char(1)',
                    'oci' => 'CHAR(1)',
                ],
            ],
            '$this->date()->notNull()' => [
                SchemaInterface::TYPE_DATE . ' NOT NULL',
                [
                    'pgsql' => 'date NOT NULL',
                    'sqlite' => 'date NOT NULL',
                    'oci' => 'DATE NOT NULL',
                    'sqlsrv' => 'date NOT NULL',
                ],
            ],
            '$this->date()' => [
                SchemaInterface::TYPE_DATE,
                [
                    'mysql' => 'date',
                    'pgsql' => 'date',
                    'sqlite' => 'date',
                    'oci' => 'DATE',
                    'sqlsrv' => 'date',
                ],
            ],
            '$this->dateTime()->notNull()' => [
                SchemaInterface::TYPE_DATETIME . ' NOT NULL',
                [
                    'mysql' => 'datetime(0) NOT NULL',
                    'pgsql' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'datetime NOT NULL',
                    'oci' => 'TIMESTAMP(0) NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            '$this->dateTime()' => [
                SchemaInterface::TYPE_DATETIME,
                [
                    'mysql' => 'datetime(0)',
                    'pgsql' => 'timestamp(0)',
                    'sqlite' => 'datetime',
                    'oci' => 'TIMESTAMP(0)',
                    'sqlsrv' => 'datetime',
                ],
            ],
            '$this->decimal()->check(\'value > 5.6\')' => [
                SchemaInterface::TYPE_DECIMAL . ' CHECK (value > 5.6)',
                [
                    'mysql' => 'decimal(10,0) CHECK (value > 5.6)',
                    'pgsql' => 'numeric(10,0) CHECK (value > 5.6)',
                    'sqlite' => 'decimal(10,0) CHECK (value > 5.6)',
                    'oci' => 'NUMBER(10,0) CHECK (value > 5.6)',
                    'sqlsrv' => 'decimal(18,0) CHECK (value > 5.6)',
                ],
            ],
            '$this->decimal()->notNull()' => [
                SchemaInterface::TYPE_DECIMAL . ' NOT NULL',
                [
                    'mysql' => 'decimal(10,0) NOT NULL',
                    'pgsql' => 'numeric(10,0) NOT NULL',
                    'sqlite' => 'decimal(10,0) NOT NULL',
                    'oci' => 'NUMBER(10,0) NOT NULL',
                    'sqlsrv' => 'decimal(18,0) NOT NULL',
                ],
            ],
            '$this->decimal(12, 4)->check(\'value > 5.6\')' => [
                SchemaInterface::TYPE_DECIMAL . '(12,4) CHECK (value > 5.6)',
                [
                    'mysql' => 'decimal(12,4) CHECK (value > 5.6)',
                    'pgsql' => 'numeric(12,4) CHECK (value > 5.6)',
                    'sqlite' => 'decimal(12,4) CHECK (value > 5.6)',
                    'oci' => 'NUMBER(12,4) CHECK (value > 5.6)',
                    'sqlsrv' => 'decimal(12,4) CHECK (value > 5.6)',
                ],
            ],
            '$this->decimal(12, 4)' => [
                SchemaInterface::TYPE_DECIMAL . '(12,4)',
                [
                    'mysql' => 'decimal(12,4)',
                    'pgsql' => 'numeric(12,4)',
                    'sqlite' => 'decimal(12,4)',
                    'oci' => 'NUMBER(12,4)',
                    'sqlsrv' => 'decimal(12,4)',
                ],
            ],
            '$this->decimal()' => [
                SchemaInterface::TYPE_DECIMAL,
                [
                    'mysql' => 'decimal(10,0)',
                    'pgsql' => 'numeric(10,0)',
                    'sqlite' => 'decimal(10,0)',
                    'oci' => 'NUMBER(10,0)',
                    'sqlsrv' => 'decimal(18,0)',
                ],
            ],
            '$this->double()->check(\'value > 5.6\')' => [
                SchemaInterface::TYPE_DOUBLE . ' CHECK (value > 5.6)',
                [
                    'mysql' => 'double CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'double CHECK (value > 5.6)',
                    'oci' => 'BINARY_DOUBLE CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            '$this->double()->notNull()' => [
                SchemaInterface::TYPE_DOUBLE . ' NOT NULL',
                [
                    'mysql' => 'double NOT NULL',
                    'pgsql' => 'double precision NOT NULL',
                    'sqlite' => 'double NOT NULL',
                    'oci' => 'BINARY_DOUBLE NOT NULL',
                    'sqlsrv' => 'float NOT NULL',
                ],
            ],
            '$this->double(16)->check(\'value > 5.6\')' => [
                SchemaInterface::TYPE_DOUBLE . '(16) CHECK (value > 5.6)',
                [
                    'mysql' => 'double CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'double CHECK (value > 5.6)',
                    'oci' => 'BINARY_DOUBLE CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            '$this->double(16)' => [
                SchemaInterface::TYPE_DOUBLE . '(16)',
                [
                    'mysql' => 'double',
                    'sqlite' => 'double',
                    'oci' => 'BINARY_DOUBLE',
                    'sqlsrv' => 'float',
                ],
            ],
            '$this->double()' => [
                SchemaInterface::TYPE_DOUBLE,
                [
                    'mysql' => 'double',
                    'pgsql' => 'double precision',
                    'sqlite' => 'double',
                    'oci' => 'BINARY_DOUBLE',
                    'sqlsrv' => 'float',
                ],
            ],
            '$this->float()->check(\'value > 5.6\')' => [
                SchemaInterface::TYPE_FLOAT . ' CHECK (value > 5.6)',
                [
                    'mysql' => 'float CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'float CHECK (value > 5.6)',
                    'oci' => 'BINARY_FLOAT CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            '$this->float()->notNull()' => [
                SchemaInterface::TYPE_FLOAT . ' NOT NULL',
                [
                    'mysql' => 'float NOT NULL',
                    'pgsql' => 'double precision NOT NULL',
                    'sqlite' => 'float NOT NULL',
                    'oci' => 'BINARY_FLOAT NOT NULL',
                    'sqlsrv' => 'float NOT NULL',
                ],
            ],
            '$this->float(16)->check(\'value > 5.6\')' => [
                SchemaInterface::TYPE_FLOAT . '(16) CHECK (value > 5.6)',
                [
                    'mysql' => 'float CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'float CHECK (value > 5.6)',
                    'oci' => 'BINARY_FLOAT CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            '$this->float(16)' => [
                SchemaInterface::TYPE_FLOAT . '(16)',
                [
                    'mysql' => 'float',
                    'sqlite' => 'float',
                    'oci' => 'BINARY_FLOAT',
                    'sqlsrv' => 'float',
                ],
            ],
            '$this->float()' => [
                SchemaInterface::TYPE_FLOAT,
                [
                    'mysql' => 'float',
                    'pgsql' => 'double precision',
                    'sqlite' => 'float',
                    'oci' => 'BINARY_FLOAT',
                    'sqlsrv' => 'float',
                ],
            ],
            '$this->integer()->check(\'value > 5\')' => [
                SchemaInterface::TYPE_INTEGER . ' CHECK (value > 5)',
                [
                    'mysql' => 'int(11) CHECK (value > 5)',
                    'pgsql' => 'integer CHECK (value > 5)',
                    'sqlite' => 'integer CHECK (value > 5)',
                    'oci' => 'NUMBER(10) CHECK (value > 5)',
                    'sqlsrv' => 'int CHECK (value > 5)',
                ],
            ],
            '$this->integer()->notNull()' => [
                SchemaInterface::TYPE_INTEGER . ' NOT NULL',
                [
                    'mysql' => 'int(11) NOT NULL',
                    'pgsql' => 'integer NOT NULL',
                    'sqlite' => 'integer NOT NULL',
                    'oci' => 'NUMBER(10) NOT NULL',
                    'sqlsrv' => 'int NOT NULL',
                ],
            ],
            '$this->integer(8)->check(\'value > 5\')' => [
                SchemaInterface::TYPE_INTEGER . '(8) CHECK (value > 5)',
                [
                    'mysql' => 'int(8) CHECK (value > 5)',
                    'pgsql' => 'integer CHECK (value > 5)',
                    'sqlite' => 'integer CHECK (value > 5)',
                    'oci' => 'NUMBER(8) CHECK (value > 5)',
                    'sqlsrv' => 'int CHECK (value > 5)',
                ],
            ],
            '$this->integer(8)->unsigned()' => [
                SchemaInterface::TYPE_INTEGER . '(8)',
                [
                    'pgsql' => 'integer',
                ],
            ],
            '$this->integer(8)' => [
                SchemaInterface::TYPE_INTEGER . '(8)',
                [
                    'mysql' => 'int(8)',
                    'pgsql' => 'integer',
                    'sqlite' => 'integer',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'int',
                ],
            ],
            '$this->integer()' => [
                SchemaInterface::TYPE_INTEGER,
                [
                    'mysql' => 'int(11)',
                    'pgsql' => 'integer',
                    'sqlite' => 'integer',
                    'oci' => 'NUMBER(10)',
                    'sqlsrv' => 'int',
                ],
            ],
            '$this->money()->check(\'value > 0.0\')' => [
                SchemaInterface::TYPE_MONEY . ' CHECK (value > 0.0)',
                [
                    'mysql' => 'decimal(19,4) CHECK (value > 0.0)',
                    'pgsql' => 'numeric(19,4) CHECK (value > 0.0)',
                    'sqlite' => 'decimal(19,4) CHECK (value > 0.0)',
                    'oci' => 'NUMBER(19,4) CHECK (value > 0.0)',
                    'sqlsrv' => 'decimal(19,4) CHECK (value > 0.0)',
                ],
            ],
            '$this->money()->notNull()' => [
                SchemaInterface::TYPE_MONEY . ' NOT NULL',
                [
                    'mysql' => 'decimal(19,4) NOT NULL',
                    'pgsql' => 'numeric(19,4) NOT NULL',
                    'sqlite' => 'decimal(19,4) NOT NULL',
                    'oci' => 'NUMBER(19,4) NOT NULL',
                    'sqlsrv' => 'decimal(19,4) NOT NULL',
                ],
            ],
            '$this->money(16, 2)->check(\'value > 0.0\')' => [
                SchemaInterface::TYPE_MONEY . '(16,2) CHECK (value > 0.0)',
                [
                    'mysql' => 'decimal(16,2) CHECK (value > 0.0)',
                    'pgsql' => 'numeric(16,2) CHECK (value > 0.0)',
                    'sqlite' => 'decimal(16,2) CHECK (value > 0.0)',
                    'oci' => 'NUMBER(16,2) CHECK (value > 0.0)',
                    'sqlsrv' => 'decimal(16,2) CHECK (value > 0.0)',
                ],
            ],
            '$this->money(16, 2)' => [
                SchemaInterface::TYPE_MONEY . '(16,2)',
                [
                    'mysql' => 'decimal(16,2)',
                    'pgsql' => 'numeric(16,2)',
                    'sqlite' => 'decimal(16,2)',
                    'oci' => 'NUMBER(16,2)',
                    'sqlsrv' => 'decimal(16,2)',
                ],
            ],
            '$this->money()' => [
                SchemaInterface::TYPE_MONEY,
                [
                    'mysql' => 'decimal(19,4)',
                    'pgsql' => 'numeric(19,4)',
                    'sqlite' => 'decimal(19,4)',
                    'oci' => 'NUMBER(19,4)',
                    'sqlsrv' => 'decimal(19,4)',
                ],
            ],
            '$this->primaryKey()->check(\'value > 5\')' => [
                SchemaInterface::TYPE_PK . ' CHECK (value > 5)',
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY CHECK (value > 5)',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY CHECK (value > 5)',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL CHECK (value > 5)',
                    'oci' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY CHECK (value > 5)',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY CHECK (value > 5)',
                ],
            ],
            '$this->primaryKey(8)->check(\'value > 5\')' => [
                SchemaInterface::TYPE_PK . '(8) CHECK (value > 5)',
                [
                    'mysql' => 'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY CHECK (value > 5)',
                    'oci' => 'NUMBER(8) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY CHECK (value > 5)',
                ],
            ],
            '$this->primaryKey(8)' => [
                SchemaInterface::TYPE_PK . '(8)',
                [
                    'mysql' => 'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'oci' => 'NUMBER(8) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY',
                ],
            ],
            '$this->primaryKey()' => [
                SchemaInterface::TYPE_PK,
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                    'oci' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
            ],
            '$this->tinyInteger(2)' => [
                SchemaInterface::TYPE_TINYINT . '(2)',
                [
                    'mysql' => 'tinyint(2)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'tinyint',
                    'oci' => 'NUMBER(2)',
                    'sqlsrv' => 'tinyint',
                ],
            ],
            '$this->tinyInteger()->unsigned()' => [
                SchemaInterface::TYPE_TINYINT . ' UNSIGNED',
                [
                    'mysql' => 'tinyint(3) UNSIGNED',
                    'sqlite' => 'tinyint UNSIGNED',
                ],
            ],
            '$this->tinyInteger()' => [
                SchemaInterface::TYPE_TINYINT,
                [
                    'mysql' => 'tinyint(3)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'tinyint',
                    'oci' => 'NUMBER(3)',
                    'sqlsrv' => 'tinyint',
                ],
            ],
            '$this->smallInteger(8)' => [
                SchemaInterface::TYPE_SMALLINT . '(8)',
                [
                    'mysql' => 'smallint(8)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'smallint',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'smallint',
                ],
            ],
            '$this->smallInteger()' => [
                SchemaInterface::TYPE_SMALLINT,
                [
                    'mysql' => 'smallint(6)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'smallint',
                    'oci' => 'NUMBER(5)',
                    'sqlsrv' => 'smallint',
                ],
            ],
            '$this->string()->check("value LIKE \'test%\'")' => [
                SchemaInterface::TYPE_STRING . " CHECK (value LIKE 'test%')",
                [
                    'mysql' => "varchar(255) CHECK (value LIKE 'test%')",
                    'sqlite' => "varchar(255) CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(255) CHECK (value LIKE 'test%')",
                ],
            ],
            '$this->string()->check(\'value LIKE \\\'test%\\\'\')' => [
                SchemaInterface::TYPE_STRING . ' CHECK (value LIKE \'test%\')',
                [
                    'pgsql' => 'varchar(255) CHECK (value LIKE \'test%\')',
                    'oci' => 'VARCHAR2(255) CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->string()->notNull()' => [
                SchemaInterface::TYPE_STRING . ' NOT NULL',
                [
                    'mysql' => 'varchar(255) NOT NULL',
                    'pgsql' => 'varchar(255) NOT NULL',
                    'sqlite' => 'varchar(255) NOT NULL',
                    'oci' => 'VARCHAR2(255) NOT NULL',
                    'sqlsrv' => 'nvarchar(255) NOT NULL',
                ],
            ],
            '$this->string(32)->check("value LIKE \'test%\'")' => [
                SchemaInterface::TYPE_STRING . "(32) CHECK (value LIKE 'test%')",
                [
                    'mysql' => "varchar(32) CHECK (value LIKE 'test%')",
                    'sqlite' => "varchar(32) CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(32) CHECK (value LIKE 'test%')",
                ],
            ],
            '$this->string(32)->check(\'value LIKE \\\'test%\\\'\')' => [
                SchemaInterface::TYPE_STRING . '(32) CHECK (value LIKE \'test%\')',
                [
                    'pgsql' => 'varchar(32) CHECK (value LIKE \'test%\')',
                    'oci' => 'VARCHAR2(32) CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->string(32)' => [
                SchemaInterface::TYPE_STRING . '(32)',
                [
                    'mysql' => 'varchar(32)',
                    'pgsql' => 'varchar(32)',
                    'sqlite' => 'varchar(32)',
                    'oci' => 'VARCHAR2(32)',
                    'sqlsrv' => 'nvarchar(32)',
                ],
            ],
            '$this->string()' => [
                SchemaInterface::TYPE_STRING,
                [
                    'mysql' => 'varchar(255)',
                    'pgsql' => 'varchar(255)',
                    'sqlite' => 'varchar(255)',
                    'oci' => 'VARCHAR2(255)',
                    'sqlsrv' => 'nvarchar(255)',
                ],
            ],
            '$this->text()->check("value LIKE \'test%\'")' => [
                SchemaInterface::TYPE_TEXT . " CHECK (value LIKE 'test%')",
                [
                    'mysql' => "text CHECK (value LIKE 'test%')",
                    'sqlite' => "text CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(max) CHECK (value LIKE 'test%')",
                ],
            ],
            '$this->text()->notNull()' => [
                SchemaInterface::TYPE_TEXT . ' NOT NULL',
                [
                    'mysql' => 'text NOT NULL',
                    'pgsql' => 'text NOT NULL',
                    'sqlite' => 'text NOT NULL',
                    'oci' => 'CLOB NOT NULL',
                    'sqlsrv' => 'nvarchar(max) NOT NULL',
                ],
            ],
            '$this->text()->check(\'value LIKE \\\'test%\\\'\')' => [
                SchemaInterface::TYPE_TEXT . ' CHECK (value LIKE \'test%\')',
                [
                    'pgsql' => 'text CHECK (value LIKE \'test%\')',
                    'oci' => 'CLOB CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->text()' => [
                SchemaInterface::TYPE_TEXT,
                [
                    'mysql' => 'text',
                    'pgsql' => 'text',
                    'sqlite' => 'text',
                    'oci' => 'CLOB',
                    'sqlsrv' => 'nvarchar(max)',
                ],
            ],
            '$this->time()->notNull()' => [
                SchemaInterface::TYPE_TIME . ' NOT NULL',
                [
                    'mysql' => 'time(0) NOT NULL',
                    'pgsql' => 'time(0) NOT NULL',
                    'sqlite' => 'time NOT NULL',
                    'oci' => 'INTERVAL DAY(0) TO SECOND(0) NOT NULL',
                    'sqlsrv' => 'time NOT NULL',
                ],
            ],
            '$this->time()' => [
                SchemaInterface::TYPE_TIME,
                [
                    'mysql' => 'time(0)',
                    'pgsql' => 'time(0)',
                    'sqlite' => 'time',
                    'oci' => 'INTERVAL DAY(0) TO SECOND(0)',
                    'sqlsrv' => 'time',
                ],
            ],
            '$this->timestamp()->notNull()' => [
                SchemaInterface::TYPE_TIMESTAMP . ' NOT NULL',
                [
                    'mysql' => 'timestamp(0) NOT NULL',
                    'pgsql' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'timestamp NOT NULL',
                    'oci' => 'TIMESTAMP(0) NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            '$this->timestamp()->defaultValue(null)' => [
                SchemaInterface::TYPE_TIMESTAMP . ' NULL DEFAULT NULL',
                [
                    'mysql' => 'timestamp(0) NULL DEFAULT NULL',
                    'pgsql' => 'timestamp(0) NULL DEFAULT NULL',
                    'sqlite' => 'timestamp NULL DEFAULT NULL',
                    'sqlsrv' => 'datetime NULL DEFAULT NULL',
                ],
            ],
            '$this->timestamp(4)' => [
                SchemaInterface::TYPE_TIMESTAMP . '(4)',
                [
                    'pgsql' => 'timestamp(4)',
                    'oci' => 'TIMESTAMP(4)',
                ],
            ],
            '$this->timestamp()' => [
                SchemaInterface::TYPE_TIMESTAMP,
                [
                    /**
                     * MySQL has its own TIMESTAMP test realization.
                     *
                     * {@see QueryBuilderTest::columnTypes()}
                     */
                    'pgsql' => 'timestamp(0)',
                    'sqlite' => 'timestamp',
                    'oci' => 'TIMESTAMP(0)',
                    'sqlsrv' => 'datetime',
                ],
            ],
            '$this->primaryKey()->unsigned()' => [
                SchemaInterface::TYPE_UPK,
                [
                    'mysql' => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            '$this->bigPrimaryKey()->unsigned()' => [
                SchemaInterface::TYPE_UBIGPK,
                [
                    'mysql' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            '$this->integer()->comment(\'test comment\')' => [
                SchemaInterface::TYPE_INTEGER . " COMMENT 'test comment'",
                [
                    'mysql' => "int(11) COMMENT 'test comment'",
                    'sqlsrv' => 'int',
                ],
            ],
            '$this->primaryKey()->comment(\'test comment\')' => [
                SchemaInterface::TYPE_PK . " COMMENT 'test comment'",
                [
                    'mysql' => "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'test comment'",
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
            ],
            '$this->json()' => [
                SchemaInterface::TYPE_JSON,
                [
                    'pgsql' => 'jsonb',
                ],
            ],
        ];

        $driverName = $this->db->getDriverName();

        foreach ($items as $i => $item) {
            if (array_key_exists($driverName, $item[1])) {
                $item[1] = $item[1][$driverName];
                $items[$i] = $item;
            } else {
                unset($items[$i]);
            }
        }

        return array_values($items);
    }
}
