<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;

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
                ColumnType::BIGINT,
                [
                    'mysql' => 'bigint(20)',
                    'pgsql' => 'bigint',
                    'sqlite' => 'bigint',
                    'oci' => 'NUMBER(20)',
                    'sqlsrv' => 'bigint',
                ],
            ],
            '$this->bigInteger()->notNull()' => [
                ColumnType::BIGINT . ' NOT NULL',
                [
                    'mysql' => 'bigint(20) NOT NULL',
                    'pgsql' => 'bigint NOT NULL',
                    'sqlite' => 'bigint NOT NULL',
                    'oci' => 'NUMBER(20) NOT NULL',
                    'sqlsrv' => 'bigint NOT NULL',
                ],
            ],
            '$this->bigInteger()->check(\'value > 5\')' => [
                ColumnType::BIGINT . ' CHECK (value > 5)',
                [
                    'mysql' => 'bigint(20) CHECK (value > 5)',
                    'pgsql' => 'bigint CHECK (value > 5)',
                    'sqlite' => 'bigint CHECK (value > 5)',
                    'oci' => 'NUMBER(20) CHECK (value > 5)',
                    'sqlsrv' => 'bigint CHECK (value > 5)',
                ],
            ],
            '$this->bigInteger(8)' => [
                ColumnType::BIGINT . '(8)',
                [
                    'mysql' => 'bigint(8)',
                    'pgsql' => 'bigint',
                    'sqlite' => 'bigint',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'bigint',
                ],
            ],
            '$this->bigInteger(8)->check(\'value > 5\')' => [
                ColumnType::BIGINT . '(8) CHECK (value > 5)',
                [
                    'mysql' => 'bigint(8) CHECK (value > 5)',
                    'pgsql' => 'bigint CHECK (value > 5)',
                    'sqlite' => 'bigint CHECK (value > 5)',
                    'oci' => 'NUMBER(8) CHECK (value > 5)',
                    'sqlsrv' => 'bigint CHECK (value > 5)',
                ],
            ],
            '$this->bigPrimaryKey()' => [
                PseudoType::BIGPK,
                [
                    'mysql' => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            '$this->binary()' => [
                ColumnType::BINARY,
                [
                    'mysql' => 'blob',
                    'pgsql' => 'bytea',
                    'sqlite' => 'blob',
                    'oci' => 'BLOB',
                    'sqlsrv' => 'varbinary(max)',
                ],
            ],
            '$this->boolean()->notNull()->defaultValue(1)' => [
                ColumnType::BOOLEAN . ' NOT NULL DEFAULT 1',
                [
                    'mysql' => 'bit(1) NOT NULL DEFAULT 1',
                    'sqlite' => 'boolean NOT NULL DEFAULT 1',
                    'sqlsrv' => 'bit NOT NULL DEFAULT 1',
                ],
            ],
            '$this->boolean()->notNull()->defaultValue(true)' => [
                ColumnType::BOOLEAN . ' NOT NULL DEFAULT TRUE',
                [
                    'pgsql' => 'boolean NOT NULL DEFAULT TRUE',
                ],
            ],
            '$this->boolean()' => [
                ColumnType::BOOLEAN,
                [
                    'mysql' => 'bit(1)',
                    'pgsql' => 'boolean',
                    'sqlite' => 'boolean',
                    'oci' => 'NUMBER(1)',
                    'sqlsrv' => 'bit',
                ],
            ],
            '$this->char()->check(\'value LIKE \\\'test%\\\'\')' => [
                ColumnType::CHAR . ' CHECK (value LIKE \'test%\')',
                [
                    'pgsql' => 'char(1) CHECK (value LIKE \'test%\')',
                    'mysql' => 'char(1) CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->char()->check(\'value LIKE "test%"\')' => [
                ColumnType::CHAR . ' CHECK (value LIKE "test%")',
                [
                    'sqlite' => 'char(1) CHECK (value LIKE "test%")',
                ],
            ],
            '$this->char()->notNull()' => [
                ColumnType::CHAR . ' NOT NULL',
                [
                    'mysql' => 'char(1) NOT NULL',
                    'pgsql' => 'char(1) NOT NULL',
                    'sqlite' => 'char(1) NOT NULL',
                    'oci' => 'CHAR(1) NOT NULL',
                ],
            ],
            '$this->char(6)->check(\'value LIKE "test%"\')' => [
                ColumnType::CHAR . '(6) CHECK (value LIKE "test%")',
                [
                    'sqlite' => 'char(6) CHECK (value LIKE "test%")',
                ],
            ],
            '$this->char(6)->check(\'value LIKE \\\'test%\\\'\')' => [
                ColumnType::CHAR . '(6) CHECK (value LIKE \'test%\')',
                [
                    'mysql' => 'char(6) CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->char(6)->unsigned()' => [
                ColumnType::CHAR . '(6)',
                [
                    'pgsql' => 'char(6)',
                ],
            ],
            '$this->char(6)' => [
                ColumnType::CHAR . '(6)',
                [
                    'mysql' => 'char(6)',
                    'pgsql' => 'char(6)',
                    'sqlite' => 'char(6)',
                    'oci' => 'CHAR(6)',
                ],
            ],
            '$this->char()' => [
                ColumnType::CHAR,
                [
                    'mysql' => 'char(1)',
                    'pgsql' => 'char(1)',
                    'sqlite' => 'char(1)',
                    'oci' => 'CHAR(1)',
                ],
            ],
            '$this->date()->notNull()' => [
                ColumnType::DATE . ' NOT NULL',
                [
                    'pgsql' => 'date NOT NULL',
                    'sqlite' => 'date NOT NULL',
                    'oci' => 'DATE NOT NULL',
                    'sqlsrv' => 'date NOT NULL',
                ],
            ],
            '$this->date()' => [
                ColumnType::DATE,
                [
                    'mysql' => 'date',
                    'pgsql' => 'date',
                    'sqlite' => 'date',
                    'oci' => 'DATE',
                    'sqlsrv' => 'date',
                ],
            ],
            '$this->dateTime()->notNull()' => [
                ColumnType::DATETIME . ' NOT NULL',
                [
                    'mysql' => 'datetime(0) NOT NULL',
                    'pgsql' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'datetime NOT NULL',
                    'oci' => 'TIMESTAMP(0) NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            '$this->dateTime()' => [
                ColumnType::DATETIME,
                [
                    'mysql' => 'datetime(0)',
                    'pgsql' => 'timestamp(0)',
                    'sqlite' => 'datetime',
                    'oci' => 'TIMESTAMP(0)',
                    'sqlsrv' => 'datetime',
                ],
            ],
            '$this->decimal()->check(\'value > 5.6\')' => [
                ColumnType::DECIMAL . ' CHECK (value > 5.6)',
                [
                    'mysql' => 'decimal(10,0) CHECK (value > 5.6)',
                    'pgsql' => 'numeric(10,0) CHECK (value > 5.6)',
                    'sqlite' => 'decimal(10,0) CHECK (value > 5.6)',
                    'oci' => 'NUMBER(10,0) CHECK (value > 5.6)',
                    'sqlsrv' => 'decimal(18,0) CHECK (value > 5.6)',
                ],
            ],
            '$this->decimal()->notNull()' => [
                ColumnType::DECIMAL . ' NOT NULL',
                [
                    'mysql' => 'decimal(10,0) NOT NULL',
                    'pgsql' => 'numeric(10,0) NOT NULL',
                    'sqlite' => 'decimal(10,0) NOT NULL',
                    'oci' => 'NUMBER(10,0) NOT NULL',
                    'sqlsrv' => 'decimal(18,0) NOT NULL',
                ],
            ],
            '$this->decimal(12, 4)->check(\'value > 5.6\')' => [
                ColumnType::DECIMAL . '(12,4) CHECK (value > 5.6)',
                [
                    'mysql' => 'decimal(12,4) CHECK (value > 5.6)',
                    'pgsql' => 'numeric(12,4) CHECK (value > 5.6)',
                    'sqlite' => 'decimal(12,4) CHECK (value > 5.6)',
                    'oci' => 'NUMBER(12,4) CHECK (value > 5.6)',
                    'sqlsrv' => 'decimal(12,4) CHECK (value > 5.6)',
                ],
            ],
            '$this->decimal(12, 4)' => [
                ColumnType::DECIMAL . '(12,4)',
                [
                    'mysql' => 'decimal(12,4)',
                    'pgsql' => 'numeric(12,4)',
                    'sqlite' => 'decimal(12,4)',
                    'oci' => 'NUMBER(12,4)',
                    'sqlsrv' => 'decimal(12,4)',
                ],
            ],
            '$this->decimal()' => [
                ColumnType::DECIMAL,
                [
                    'mysql' => 'decimal(10,0)',
                    'pgsql' => 'numeric(10,0)',
                    'sqlite' => 'decimal(10,0)',
                    'oci' => 'NUMBER(10,0)',
                    'sqlsrv' => 'decimal(18,0)',
                ],
            ],
            '$this->double()->check(\'value > 5.6\')' => [
                ColumnType::DOUBLE . ' CHECK (value > 5.6)',
                [
                    'mysql' => 'double CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'double CHECK (value > 5.6)',
                    'oci' => 'BINARY_DOUBLE CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            '$this->double()->notNull()' => [
                ColumnType::DOUBLE . ' NOT NULL',
                [
                    'mysql' => 'double NOT NULL',
                    'pgsql' => 'double precision NOT NULL',
                    'sqlite' => 'double NOT NULL',
                    'oci' => 'BINARY_DOUBLE NOT NULL',
                    'sqlsrv' => 'float NOT NULL',
                ],
            ],
            '$this->double(16)->check(\'value > 5.6\')' => [
                ColumnType::DOUBLE . '(16) CHECK (value > 5.6)',
                [
                    'mysql' => 'double CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'double CHECK (value > 5.6)',
                    'oci' => 'BINARY_DOUBLE CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            '$this->double(16)' => [
                ColumnType::DOUBLE . '(16)',
                [
                    'mysql' => 'double',
                    'sqlite' => 'double',
                    'oci' => 'BINARY_DOUBLE',
                    'sqlsrv' => 'float',
                ],
            ],
            '$this->double()' => [
                ColumnType::DOUBLE,
                [
                    'mysql' => 'double',
                    'pgsql' => 'double precision',
                    'sqlite' => 'double',
                    'oci' => 'BINARY_DOUBLE',
                    'sqlsrv' => 'float',
                ],
            ],
            '$this->float()->check(\'value > 5.6\')' => [
                ColumnType::FLOAT . ' CHECK (value > 5.6)',
                [
                    'mysql' => 'float CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'float CHECK (value > 5.6)',
                    'oci' => 'BINARY_FLOAT CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            '$this->float()->notNull()' => [
                ColumnType::FLOAT . ' NOT NULL',
                [
                    'mysql' => 'float NOT NULL',
                    'pgsql' => 'double precision NOT NULL',
                    'sqlite' => 'float NOT NULL',
                    'oci' => 'BINARY_FLOAT NOT NULL',
                    'sqlsrv' => 'float NOT NULL',
                ],
            ],
            '$this->float(16)->check(\'value > 5.6\')' => [
                ColumnType::FLOAT . '(16) CHECK (value > 5.6)',
                [
                    'mysql' => 'float CHECK (value > 5.6)',
                    'pgsql' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'float CHECK (value > 5.6)',
                    'oci' => 'BINARY_FLOAT CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            '$this->float(16)' => [
                ColumnType::FLOAT . '(16)',
                [
                    'mysql' => 'float',
                    'sqlite' => 'float',
                    'oci' => 'BINARY_FLOAT',
                    'sqlsrv' => 'float',
                ],
            ],
            '$this->float()' => [
                ColumnType::FLOAT,
                [
                    'mysql' => 'float',
                    'pgsql' => 'double precision',
                    'sqlite' => 'float',
                    'oci' => 'BINARY_FLOAT',
                    'sqlsrv' => 'float',
                ],
            ],
            '$this->integer()->check(\'value > 5\')' => [
                ColumnType::INTEGER . ' CHECK (value > 5)',
                [
                    'mysql' => 'int(11) CHECK (value > 5)',
                    'pgsql' => 'integer CHECK (value > 5)',
                    'sqlite' => 'integer CHECK (value > 5)',
                    'oci' => 'NUMBER(10) CHECK (value > 5)',
                    'sqlsrv' => 'int CHECK (value > 5)',
                ],
            ],
            '$this->integer()->notNull()' => [
                ColumnType::INTEGER . ' NOT NULL',
                [
                    'mysql' => 'int(11) NOT NULL',
                    'pgsql' => 'integer NOT NULL',
                    'sqlite' => 'integer NOT NULL',
                    'oci' => 'NUMBER(10) NOT NULL',
                    'sqlsrv' => 'int NOT NULL',
                ],
            ],
            '$this->integer(8)->check(\'value > 5\')' => [
                ColumnType::INTEGER . '(8) CHECK (value > 5)',
                [
                    'mysql' => 'int(8) CHECK (value > 5)',
                    'pgsql' => 'integer CHECK (value > 5)',
                    'sqlite' => 'integer CHECK (value > 5)',
                    'oci' => 'NUMBER(8) CHECK (value > 5)',
                    'sqlsrv' => 'int CHECK (value > 5)',
                ],
            ],
            '$this->integer(8)->unsigned()' => [
                ColumnType::INTEGER . '(8)',
                [
                    'pgsql' => 'integer',
                ],
            ],
            '$this->integer(8)' => [
                ColumnType::INTEGER . '(8)',
                [
                    'mysql' => 'int(8)',
                    'pgsql' => 'integer',
                    'sqlite' => 'integer',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'int',
                ],
            ],
            '$this->integer()' => [
                ColumnType::INTEGER,
                [
                    'mysql' => 'int(11)',
                    'pgsql' => 'integer',
                    'sqlite' => 'integer',
                    'oci' => 'NUMBER(10)',
                    'sqlsrv' => 'int',
                ],
            ],
            '$this->money()->check(\'value > 0.0\')' => [
                ColumnType::MONEY . ' CHECK (value > 0.0)',
                [
                    'mysql' => 'decimal(19,4) CHECK (value > 0.0)',
                    'pgsql' => 'numeric(19,4) CHECK (value > 0.0)',
                    'sqlite' => 'decimal(19,4) CHECK (value > 0.0)',
                    'oci' => 'NUMBER(19,4) CHECK (value > 0.0)',
                    'sqlsrv' => 'decimal(19,4) CHECK (value > 0.0)',
                ],
            ],
            '$this->money()->notNull()' => [
                ColumnType::MONEY . ' NOT NULL',
                [
                    'mysql' => 'decimal(19,4) NOT NULL',
                    'pgsql' => 'numeric(19,4) NOT NULL',
                    'sqlite' => 'decimal(19,4) NOT NULL',
                    'oci' => 'NUMBER(19,4) NOT NULL',
                    'sqlsrv' => 'decimal(19,4) NOT NULL',
                ],
            ],
            '$this->money(16, 2)->check(\'value > 0.0\')' => [
                ColumnType::MONEY . '(16,2) CHECK (value > 0.0)',
                [
                    'mysql' => 'decimal(16,2) CHECK (value > 0.0)',
                    'pgsql' => 'numeric(16,2) CHECK (value > 0.0)',
                    'sqlite' => 'decimal(16,2) CHECK (value > 0.0)',
                    'oci' => 'NUMBER(16,2) CHECK (value > 0.0)',
                    'sqlsrv' => 'decimal(16,2) CHECK (value > 0.0)',
                ],
            ],
            '$this->money(16, 2)' => [
                ColumnType::MONEY . '(16,2)',
                [
                    'mysql' => 'decimal(16,2)',
                    'pgsql' => 'numeric(16,2)',
                    'sqlite' => 'decimal(16,2)',
                    'oci' => 'NUMBER(16,2)',
                    'sqlsrv' => 'decimal(16,2)',
                ],
            ],
            '$this->money()' => [
                ColumnType::MONEY,
                [
                    'mysql' => 'decimal(19,4)',
                    'pgsql' => 'numeric(19,4)',
                    'sqlite' => 'decimal(19,4)',
                    'oci' => 'NUMBER(19,4)',
                    'sqlsrv' => 'decimal(19,4)',
                ],
            ],
            '$this->primaryKey()->check(\'value > 5\')' => [
                PseudoType::PK . ' CHECK (value > 5)',
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY CHECK (value > 5)',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY CHECK (value > 5)',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL CHECK (value > 5)',
                    'oci' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY CHECK (value > 5)',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY CHECK (value > 5)',
                ],
            ],
            '$this->primaryKey(8)->check(\'value > 5\')' => [
                PseudoType::PK . '(8) CHECK (value > 5)',
                [
                    'mysql' => 'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY CHECK (value > 5)',
                    'oci' => 'NUMBER(8) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY CHECK (value > 5)',
                ],
            ],
            '$this->primaryKey(8)' => [
                PseudoType::PK . '(8)',
                [
                    'mysql' => 'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'oci' => 'NUMBER(8) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY',
                ],
            ],
            '$this->primaryKey()' => [
                PseudoType::PK,
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                    'oci' => 'NUMBER(10) GENERATED BY DEFAULT AS IDENTITY NOT NULL PRIMARY KEY',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
            ],
            '$this->tinyInteger(2)' => [
                ColumnType::TINYINT . '(2)',
                [
                    'mysql' => 'tinyint(2)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'tinyint',
                    'oci' => 'NUMBER(2)',
                    'sqlsrv' => 'tinyint',
                ],
            ],
            '$this->tinyInteger()->unsigned()' => [
                ColumnType::TINYINT . ' UNSIGNED',
                [
                    'mysql' => 'tinyint(3) UNSIGNED',
                    'sqlite' => 'tinyint UNSIGNED',
                ],
            ],
            '$this->tinyInteger()' => [
                ColumnType::TINYINT,
                [
                    'mysql' => 'tinyint(3)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'tinyint',
                    'oci' => 'NUMBER(3)',
                    'sqlsrv' => 'tinyint',
                ],
            ],
            '$this->smallInteger(8)' => [
                ColumnType::SMALLINT . '(8)',
                [
                    'mysql' => 'smallint(8)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'smallint',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'smallint',
                ],
            ],
            '$this->smallInteger()' => [
                ColumnType::SMALLINT,
                [
                    'mysql' => 'smallint(6)',
                    'pgsql' => 'smallint',
                    'sqlite' => 'smallint',
                    'oci' => 'NUMBER(5)',
                    'sqlsrv' => 'smallint',
                ],
            ],
            '$this->string()->check("value LIKE \'test%\'")' => [
                ColumnType::STRING . " CHECK (value LIKE 'test%')",
                [
                    'mysql' => "varchar(255) CHECK (value LIKE 'test%')",
                    'sqlite' => "varchar(255) CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(255) CHECK (value LIKE 'test%')",
                ],
            ],
            '$this->string()->check(\'value LIKE \\\'test%\\\'\')' => [
                ColumnType::STRING . ' CHECK (value LIKE \'test%\')',
                [
                    'pgsql' => 'varchar(255) CHECK (value LIKE \'test%\')',
                    'oci' => 'VARCHAR2(255) CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->string()->notNull()' => [
                ColumnType::STRING . ' NOT NULL',
                [
                    'mysql' => 'varchar(255) NOT NULL',
                    'pgsql' => 'varchar(255) NOT NULL',
                    'sqlite' => 'varchar(255) NOT NULL',
                    'oci' => 'VARCHAR2(255) NOT NULL',
                    'sqlsrv' => 'nvarchar(255) NOT NULL',
                ],
            ],
            '$this->string(32)->check("value LIKE \'test%\'")' => [
                ColumnType::STRING . "(32) CHECK (value LIKE 'test%')",
                [
                    'mysql' => "varchar(32) CHECK (value LIKE 'test%')",
                    'sqlite' => "varchar(32) CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(32) CHECK (value LIKE 'test%')",
                ],
            ],
            '$this->string(32)->check(\'value LIKE \\\'test%\\\'\')' => [
                ColumnType::STRING . '(32) CHECK (value LIKE \'test%\')',
                [
                    'pgsql' => 'varchar(32) CHECK (value LIKE \'test%\')',
                    'oci' => 'VARCHAR2(32) CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->string(32)' => [
                ColumnType::STRING . '(32)',
                [
                    'mysql' => 'varchar(32)',
                    'pgsql' => 'varchar(32)',
                    'sqlite' => 'varchar(32)',
                    'oci' => 'VARCHAR2(32)',
                    'sqlsrv' => 'nvarchar(32)',
                ],
            ],
            '$this->string()' => [
                ColumnType::STRING,
                [
                    'mysql' => 'varchar(255)',
                    'pgsql' => 'varchar(255)',
                    'sqlite' => 'varchar(255)',
                    'oci' => 'VARCHAR2(255)',
                    'sqlsrv' => 'nvarchar(255)',
                ],
            ],
            '$this->text()->check("value LIKE \'test%\'")' => [
                ColumnType::TEXT . " CHECK (value LIKE 'test%')",
                [
                    'mysql' => "text CHECK (value LIKE 'test%')",
                    'sqlite' => "text CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(max) CHECK (value LIKE 'test%')",
                ],
            ],
            '$this->text()->notNull()' => [
                ColumnType::TEXT . ' NOT NULL',
                [
                    'mysql' => 'text NOT NULL',
                    'pgsql' => 'text NOT NULL',
                    'sqlite' => 'text NOT NULL',
                    'oci' => 'CLOB NOT NULL',
                    'sqlsrv' => 'nvarchar(max) NOT NULL',
                ],
            ],
            '$this->text()->check(\'value LIKE \\\'test%\\\'\')' => [
                ColumnType::TEXT . ' CHECK (value LIKE \'test%\')',
                [
                    'pgsql' => 'text CHECK (value LIKE \'test%\')',
                    'oci' => 'CLOB CHECK (value LIKE \'test%\')',
                ],
            ],
            '$this->text()' => [
                ColumnType::TEXT,
                [
                    'mysql' => 'text',
                    'pgsql' => 'text',
                    'sqlite' => 'text',
                    'oci' => 'CLOB',
                    'sqlsrv' => 'nvarchar(max)',
                ],
            ],
            '$this->time()->notNull()' => [
                ColumnType::TIME . ' NOT NULL',
                [
                    'mysql' => 'time(0) NOT NULL',
                    'pgsql' => 'time(0) NOT NULL',
                    'sqlite' => 'time NOT NULL',
                    'oci' => 'INTERVAL DAY(0) TO SECOND(0) NOT NULL',
                    'sqlsrv' => 'time NOT NULL',
                ],
            ],
            '$this->time()' => [
                ColumnType::TIME,
                [
                    'mysql' => 'time(0)',
                    'pgsql' => 'time(0)',
                    'sqlite' => 'time',
                    'oci' => 'INTERVAL DAY(0) TO SECOND(0)',
                    'sqlsrv' => 'time',
                ],
            ],
            '$this->timestamp()->notNull()' => [
                ColumnType::TIMESTAMP . ' NOT NULL',
                [
                    'mysql' => 'timestamp(0) NOT NULL',
                    'pgsql' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'timestamp NOT NULL',
                    'oci' => 'TIMESTAMP(0) NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            '$this->timestamp()->defaultValue(null)' => [
                ColumnType::TIMESTAMP . ' NULL DEFAULT NULL',
                [
                    'mysql' => 'timestamp(0) NULL DEFAULT NULL',
                    'pgsql' => 'timestamp(0) NULL DEFAULT NULL',
                    'sqlite' => 'timestamp NULL DEFAULT NULL',
                    'sqlsrv' => 'datetime NULL DEFAULT NULL',
                ],
            ],
            '$this->timestamp(4)' => [
                ColumnType::TIMESTAMP . '(4)',
                [
                    'pgsql' => 'timestamp(4)',
                    'oci' => 'TIMESTAMP(4)',
                ],
            ],
            '$this->timestamp()' => [
                ColumnType::TIMESTAMP,
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
                PseudoType::UPK,
                [
                    'mysql' => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            '$this->bigPrimaryKey()->unsigned()' => [
                PseudoType::UBIGPK,
                [
                    'mysql' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            '$this->integer()->comment(\'test comment\')' => [
                ColumnType::INTEGER . " COMMENT 'test comment'",
                [
                    'mysql' => "int(11) COMMENT 'test comment'",
                    'sqlsrv' => 'int',
                ],
            ],
            '$this->primaryKey()->comment(\'test comment\')' => [
                PseudoType::PK . " COMMENT 'test comment'",
                [
                    'mysql' => "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'test comment'",
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
            ],
            '$this->json()' => [
                ColumnType::JSON,
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
