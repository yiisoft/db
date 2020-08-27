<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

use Closure;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\BetweenColumnsCondition;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Db\Schema\ColumnSchema;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaBuilderTrait;

use function array_key_exists;
use function array_merge;
use function array_values;
use function is_array;
use function is_string;
use function preg_match_all;
use function str_replace;
use function strncmp;
use function substr;

trait TestQueryBuilderTrait
{
    use SchemaBuilderTrait;

    public function getDb(): Connection
    {
        return $this->getConnection();
    }

    /**
     * This is not used as a dataprovider for testGetColumnType to speed up the test when used as dataprovider every
     * single line will cause a reconnect with the database which is not needed here.
     */
    public function columnTypes(): array
    {
        $items = [
            [
                Schema::TYPE_BIGINT,
                $this->bigInteger(),
                [
                    'mysql' => 'bigint(20)',
                    'postgres' => 'bigint',
                    'sqlite' => 'bigint',
                    'oci' => 'NUMBER(20)',
                    'sqlsrv' => 'bigint',
                ],
            ],
            [
                Schema::TYPE_BIGINT . ' NOT NULL',
                $this->bigInteger()->notNull(),
                [
                    'mysql' => 'bigint(20) NOT NULL',
                    'postgres' => 'bigint NOT NULL',
                    'sqlite' => 'bigint NOT NULL',
                    'oci' => 'NUMBER(20) NOT NULL',
                    'sqlsrv' => 'bigint NOT NULL',
                ],
            ],
            [
                Schema::TYPE_BIGINT . ' CHECK (value > 5)',
                $this->bigInteger()->check('value > 5'),
                [
                    'mysql' => 'bigint(20) CHECK (value > 5)',
                    'postgres' => 'bigint CHECK (value > 5)',
                    'sqlite' => 'bigint CHECK (value > 5)',
                    'oci' => 'NUMBER(20) CHECK (value > 5)',
                    'sqlsrv' => 'bigint CHECK (value > 5)',
                ],
            ],
            [
                Schema::TYPE_BIGINT . '(8)',
                $this->bigInteger(8),
                [
                    'mysql' => 'bigint(8)',
                    'postgres' => 'bigint',
                    'sqlite' => 'bigint',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'bigint',
                ],
            ],
            [
                Schema::TYPE_BIGINT . '(8) CHECK (value > 5)',
                $this->bigInteger(8)->check('value > 5'),
                [
                    'mysql' => 'bigint(8) CHECK (value > 5)',
                    'postgres' => 'bigint CHECK (value > 5)',
                    'sqlite' => 'bigint CHECK (value > 5)',
                    'oci' => 'NUMBER(8) CHECK (value > 5)',
                    'sqlsrv' => 'bigint CHECK (value > 5)',
                ],
            ],
            [
                Schema::TYPE_BIGPK,
                $this->bigPrimaryKey(),
                [
                    'mysql' => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'postgres' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            [
                Schema::TYPE_BINARY,
                $this->binary(),
                [
                    'mysql' => 'blob',
                    'postgres' => 'bytea',
                    'sqlite' => 'blob',
                    'oci' => 'BLOB',
                    'sqlsrv' => 'varbinary(max)',
                ],
            ],
            [
                Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT 1',
                $this->boolean()->notNull()->defaultValue(1),
                [
                    'mysql' => 'tinyint(1) NOT NULL DEFAULT 1',
                    'sqlite' => 'boolean NOT NULL DEFAULT 1',
                    'sqlsrv' => 'bit NOT NULL DEFAULT 1',
                ],
            ],
            [
                Schema::TYPE_BOOLEAN,
                $this->boolean(),
                [
                    'mysql' => 'tinyint(1)',
                    'postgres' => 'boolean',
                    'sqlite' => 'boolean',
                    'oci' => 'NUMBER(1)',
                    'sqlsrv' => 'bit',
                ],
            ],
            [
                Schema::TYPE_CHAR . ' CHECK (value LIKE "test%")',
                $this->char()->check('value LIKE "test%"'),
                [
                    'mysql' => 'char(1) CHECK (value LIKE "test%")',
                    'sqlite' => 'char(1) CHECK (value LIKE "test%")',
                ],
            ],
            [
                Schema::TYPE_CHAR . ' NOT NULL',
                $this->char()->notNull(),
                [
                    'mysql' => 'char(1) NOT NULL',
                    'postgres' => 'char(1) NOT NULL',
                    'sqlite' => 'char(1) NOT NULL',
                    'oci' => 'CHAR(1) NOT NULL',
                ],
            ],
            [
                Schema::TYPE_CHAR . '(6) CHECK (value LIKE "test%")',
                $this->char(6)->check('value LIKE "test%"'),
                [
                    'mysql' => 'char(6) CHECK (value LIKE "test%")',
                    'sqlite' => 'char(6) CHECK (value LIKE "test%")',
                ],
            ],
            [
                Schema::TYPE_CHAR . '(6)',
                $this->char(6),
                [
                    'mysql' => 'char(6)',
                    'postgres' => 'char(6)',
                    'sqlite' => 'char(6)',
                    'oci' => 'CHAR(6)',
                ],
            ],
            [
                Schema::TYPE_CHAR,
                $this->char(),
                [
                    'mysql' => 'char(1)',
                    'postgres' => 'char(1)',
                    'sqlite' => 'char(1)',
                    'oci' => 'CHAR(1)',
                ],
            ],
            [
                Schema::TYPE_DATE . ' NOT NULL',
                $this->date()->notNull(),
                [
                    'mysql' => 'date NOT NULL',
                    'postgres' => 'date NOT NULL',
                    'sqlite' => 'date NOT NULL',
                    'oci' => 'DATE NOT NULL',
                    'sqlsrv' => 'date NOT NULL',
                ],
            ],
            [
                Schema::TYPE_DATE,
                $this->date(),
                [
                    'mysql' => 'date',
                    'postgres' => 'date',
                    'sqlite' => 'date',
                    'oci' => 'DATE',
                    'sqlsrv' => 'date',
                ],
            ],
            [
                Schema::TYPE_DATETIME . ' NOT NULL',
                $this->dateTime()->notNull(),
                [
                    'postgres' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'datetime NOT NULL',
                    'oci' => 'TIMESTAMP NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            [
                Schema::TYPE_DATETIME,
                $this->dateTime(),
                [
                    'postgres' => 'timestamp(0)',
                    'sqlite' => 'datetime',
                    'oci' => 'TIMESTAMP',
                    'sqlsrv' => 'datetime',
                ],
            ],
            [
                Schema::TYPE_DECIMAL . ' CHECK (value > 5.6)',
                $this->decimal()->check('value > 5.6'),
                [
                    'mysql' => 'decimal(10,0) CHECK (value > 5.6)',
                    'postgres' => 'numeric(10,0) CHECK (value > 5.6)',
                    'sqlite' => 'decimal(10,0) CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'sqlsrv' => 'decimal(18,0) CHECK (value > 5.6)',
                ],
            ],
            [
                Schema::TYPE_DECIMAL . ' NOT NULL',
                $this->decimal()->notNull(),
                [
                    'mysql' => 'decimal(10,0) NOT NULL',
                    'postgres' => 'numeric(10,0) NOT NULL',
                    'sqlite' => 'decimal(10,0) NOT NULL',
                    'oci' => 'NUMBER NOT NULL',
                    'sqlsrv' => 'decimal(18,0) NOT NULL',
                ],
            ],
            [
                Schema::TYPE_DECIMAL . '(12,4) CHECK (value > 5.6)',
                $this->decimal(12, 4)->check('value > 5.6'),
                [
                    'mysql' => 'decimal(12,4) CHECK (value > 5.6)',
                    'postgres' => 'numeric(12,4) CHECK (value > 5.6)',
                    'sqlite' => 'decimal(12,4) CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'sqlsrv' => 'decimal(12,4) CHECK (value > 5.6)',
                ],
            ],
            [
                Schema::TYPE_DECIMAL . '(12,4)',
                $this->decimal(12, 4),
                [
                    'mysql' => 'decimal(12,4)',
                    'postgres' => 'numeric(12,4)',
                    'sqlite' => 'decimal(12,4)',
                    'oci' => 'NUMBER',
                    'sqlsrv' => 'decimal(12,4)',
                ],
            ],
            [
                Schema::TYPE_DECIMAL,
                $this->decimal(),
                [
                    'mysql' => 'decimal(10,0)',
                    'postgres' => 'numeric(10,0)',
                    'sqlite' => 'decimal(10,0)',
                    'oci' => 'NUMBER',
                    'sqlsrv' => 'decimal(18,0)',
                ],
            ],
            [
                Schema::TYPE_DOUBLE . ' CHECK (value > 5.6)',
                $this->double()->check('value > 5.6'),
                [
                    'mysql' => 'double CHECK (value > 5.6)',
                    'postgres' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'double CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            [
                Schema::TYPE_DOUBLE . ' NOT NULL',
                $this->double()->notNull(),
                [
                    'mysql' => 'double NOT NULL',
                    'postgres' => 'double precision NOT NULL',
                    'sqlite' => 'double NOT NULL',
                    'oci' => 'NUMBER NOT NULL',
                    'sqlsrv' => 'float NOT NULL',
                ],
            ],
            [
                Schema::TYPE_DOUBLE . '(16) CHECK (value > 5.6)',
                $this->double(16)->check('value > 5.6'),
                [
                    'mysql' => 'double CHECK (value > 5.6)',
                    'postgres' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'double CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            [
                Schema::TYPE_DOUBLE . '(16)',
                $this->double(16),
                [
                    'mysql' => 'double',
                    'sqlite' => 'double',
                    'oci' => 'NUMBER',
                    'sqlsrv' => 'float',
                ],
            ],
            [
                Schema::TYPE_DOUBLE,
                $this->double(),
                [
                    'mysql' => 'double',
                    'postgres' => 'double precision',
                    'sqlite' => 'double',
                    'oci' => 'NUMBER',
                    'sqlsrv' => 'float',
                ],
            ],
            [
                Schema::TYPE_FLOAT . ' CHECK (value > 5.6)',
                $this->float()->check('value > 5.6'),
                [
                    'mysql' => 'float CHECK (value > 5.6)',
                    'postgres' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'float CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            [
                Schema::TYPE_FLOAT . ' NOT NULL',
                $this->float()->notNull(),
                [
                    'mysql' => 'float NOT NULL',
                    'postgres' => 'double precision NOT NULL',
                    'sqlite' => 'float NOT NULL',
                    'oci' => 'NUMBER NOT NULL',
                    'sqlsrv' => 'float NOT NULL',
                ],
            ],
            [
                Schema::TYPE_FLOAT . '(16) CHECK (value > 5.6)',
                $this->float(16)->check('value > 5.6'),
                [
                    'mysql' => 'float CHECK (value > 5.6)',
                    'postgres' => 'double precision CHECK (value > 5.6)',
                    'sqlite' => 'float CHECK (value > 5.6)',
                    'oci' => 'NUMBER CHECK (value > 5.6)',
                    'sqlsrv' => 'float CHECK (value > 5.6)',
                ],
            ],
            [
                Schema::TYPE_FLOAT . '(16)',
                $this->float(16),
                [
                    'mysql' => 'float',
                    'sqlite' => 'float',
                    'oci' => 'NUMBER',
                    'sqlsrv' => 'float',
                ],
            ],
            [
                Schema::TYPE_FLOAT,
                $this->float(),
                [
                    'mysql' => 'float',
                    'postgres' => 'double precision',
                    'sqlite' => 'float',
                    'oci' => 'NUMBER',
                    'sqlsrv' => 'float',
                ],
            ],
            [
                Schema::TYPE_INTEGER . ' CHECK (value > 5)',
                $this->integer()->check('value > 5'),
                [
                    'mysql' => 'int(11) CHECK (value > 5)',
                    'postgres' => 'integer CHECK (value > 5)',
                    'sqlite' => 'integer CHECK (value > 5)',
                    'oci' => 'NUMBER(10) CHECK (value > 5)',
                    'sqlsrv' => 'int CHECK (value > 5)',
                ],
            ],
            [
                Schema::TYPE_INTEGER . ' NOT NULL',
                $this->integer()->notNull(),
                [
                    'mysql' => 'int(11) NOT NULL',
                    'postgres' => 'integer NOT NULL',
                    'sqlite' => 'integer NOT NULL',
                    'oci' => 'NUMBER(10) NOT NULL',
                    'sqlsrv' => 'int NOT NULL',
                ],
            ],
            [
                Schema::TYPE_INTEGER . '(8) CHECK (value > 5)',
                $this->integer(8)->check('value > 5'),
                [
                    'mysql' => 'int(8) CHECK (value > 5)',
                    'postgres' => 'integer CHECK (value > 5)',
                    'sqlite' => 'integer CHECK (value > 5)',
                    'oci' => 'NUMBER(8) CHECK (value > 5)',
                    'sqlsrv' => 'int CHECK (value > 5)',
                ],
            ],
            [
                Schema::TYPE_INTEGER . '(8)',
                $this->integer(8),
                [
                    'mysql' => 'int(8)',
                    'postgres' => 'integer',
                    'sqlite' => 'integer',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'int',
                ],
            ],
            [
                Schema::TYPE_INTEGER,
                $this->integer(),
                [
                    'mysql' => 'int(11)',
                    'postgres' => 'integer',
                    'sqlite' => 'integer',
                    'oci' => 'NUMBER(10)',
                    'sqlsrv' => 'int',
                ],
            ],
            [
                Schema::TYPE_MONEY . ' CHECK (value > 0.0)',
                $this->money()->check('value > 0.0'),
                [
                    'mysql' => 'decimal(19,4) CHECK (value > 0.0)',
                    'postgres' => 'numeric(19,4) CHECK (value > 0.0)',
                    'sqlite' => 'decimal(19,4) CHECK (value > 0.0)',
                    'oci' => 'NUMBER(19,4) CHECK (value > 0.0)',
                    'sqlsrv' => 'decimal(19,4) CHECK (value > 0.0)',
                ]
            ],
            [
                Schema::TYPE_MONEY . ' NOT NULL',
                $this->money()->notNull(),
                [
                    'mysql' => 'decimal(19,4) NOT NULL',
                    'postgres' => 'numeric(19,4) NOT NULL',
                    'sqlite' => 'decimal(19,4) NOT NULL',
                    'oci' => 'NUMBER(19,4) NOT NULL',
                    'sqlsrv' => 'decimal(19,4) NOT NULL',
                ],
            ],
            [
                Schema::TYPE_MONEY . '(16,2) CHECK (value > 0.0)',
                $this->money(16, 2)->check('value > 0.0'),
                [
                    'mysql' => 'decimal(16,2) CHECK (value > 0.0)',
                    'postgres' => 'numeric(16,2) CHECK (value > 0.0)',
                    'sqlite' => 'decimal(16,2) CHECK (value > 0.0)',
                    'oci' => 'NUMBER(16,2) CHECK (value > 0.0)',
                    'sqlsrv' => 'decimal(16,2) CHECK (value > 0.0)',
                ],
            ],
            [
                Schema::TYPE_MONEY . '(16,2)',
                $this->money(16, 2),
                [
                    'mysql' => 'decimal(16,2)',
                    'postgres' => 'numeric(16,2)',
                    'sqlite' => 'decimal(16,2)',
                    'oci' => 'NUMBER(16,2)',
                    'sqlsrv' => 'decimal(16,2)',
                ],
            ],
            [
                Schema::TYPE_MONEY,
                $this->money(),
                [
                    'mysql' => 'decimal(19,4)',
                    'postgres' => 'numeric(19,4)',
                    'sqlite' => 'decimal(19,4)',
                    'oci' => 'NUMBER(19,4)',
                    'sqlsrv' => 'decimal(19,4)',
                ],
            ],
            [
                Schema::TYPE_PK . ' CHECK (value > 5)',
                $this->primaryKey()->check('value > 5'),
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY CHECK (value > 5)',
                    'postgres' => 'serial NOT NULL PRIMARY KEY CHECK (value > 5)',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL CHECK (value > 5)',
                    'oci' => 'NUMBER(10) NOT NULL PRIMARY KEY CHECK (value > 5)',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY CHECK (value > 5)',
                ],
            ],
            [
                Schema::TYPE_PK . '(8) CHECK (value > 5)',
                $this->primaryKey(8)->check('value > 5'),
                [
                    'mysql' => 'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY CHECK (value > 5)',
                    'oci' => 'NUMBER(8) NOT NULL PRIMARY KEY CHECK (value > 5)',
                ],
            ],
            [
                Schema::TYPE_PK . '(8)',
                $this->primaryKey(8),
                [
                    'mysql' => 'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'oci' => 'NUMBER(8) NOT NULL PRIMARY KEY',
                ],
            ],
            [
                Schema::TYPE_PK,
                $this->primaryKey(),
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'postgres' => 'serial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                    'oci' => 'NUMBER(10) NOT NULL PRIMARY KEY',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
            ],
            [
                Schema::TYPE_TINYINT . '(2)',
                $this->tinyInteger(2),
                [
                    'mysql' => 'tinyint(2)',
                    'postgres' => 'smallint',
                    'sqlite' => 'tinyint',
                    'oci' => 'NUMBER(2)',
                    'sqlsrv' => 'tinyint',
                ],
            ],
            [
                Schema::TYPE_TINYINT . ' UNSIGNED',
                $this->tinyInteger()->unsigned(),
                [
                    'mysql' => 'tinyint(3) UNSIGNED',
                    'postgres' => 'smallint UNSIGNED',
                    'sqlite' => 'tinyint UNSIGNED',
                ],
            ],
            [
                Schema::TYPE_TINYINT,
                $this->tinyInteger(),
                [
                    'mysql' => 'tinyint(3)',
                    'postgres' => 'smallint',
                    'sqlite' => 'tinyint',
                    'oci' => 'NUMBER(3)',
                    'sqlsrv' => 'tinyint',
                ],
            ],
            [
                Schema::TYPE_SMALLINT . '(8)',
                $this->smallInteger(8),
                [
                    'mysql' => 'smallint(8)',
                    'postgres' => 'smallint',
                    'sqlite' => 'smallint',
                    'oci' => 'NUMBER(8)',
                    'sqlsrv' => 'smallint',
                ],
            ],
            [
                Schema::TYPE_SMALLINT,
                $this->smallInteger(),
                [
                    'mysql' => 'smallint(6)',
                    'postgres' => 'smallint',
                    'sqlite' => 'smallint',
                    'oci' => 'NUMBER(5)',
                    'sqlsrv' => 'smallint',
                ],
            ],
            [
                Schema::TYPE_STRING . " CHECK (value LIKE 'test%')",
                $this->string()->check("value LIKE 'test%'"),
                [
                    'mysql' => "varchar(255) CHECK (value LIKE 'test%')",
                    'sqlite' => "varchar(255) CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(255) CHECK (value LIKE 'test%')",
                ],
            ],
            [
                Schema::TYPE_STRING . ' CHECK (value LIKE \'test%\')',
                $this->string()->check('value LIKE \'test%\''),
                [
                    'postgres' => 'varchar(255) CHECK (value LIKE \'test%\')',
                    'oci' => 'VARCHAR2(255) CHECK (value LIKE \'test%\')',
                ],
            ],
            [
                Schema::TYPE_STRING . ' NOT NULL',
                $this->string()->notNull(),
                [
                    'mysql' => 'varchar(255) NOT NULL',
                    'postgres' => 'varchar(255) NOT NULL',
                    'sqlite' => 'varchar(255) NOT NULL',
                    'oci' => 'VARCHAR2(255) NOT NULL',
                    'sqlsrv' => 'nvarchar(255) NOT NULL',
                ],
            ],
            [
                Schema::TYPE_STRING . "(32) CHECK (value LIKE 'test%')",
                $this->string(32)->check("value LIKE 'test%'"),
                [
                    'mysql' => "varchar(32) CHECK (value LIKE 'test%')",
                    'sqlite' => "varchar(32) CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(32) CHECK (value LIKE 'test%')",
                ],
            ],
            [
                Schema::TYPE_STRING . '(32) CHECK (value LIKE \'test%\')',
                $this->string(32)->check('value LIKE \'test%\''),
                [
                    'postgres' => 'varchar(32) CHECK (value LIKE \'test%\')',
                    'oci' => 'VARCHAR2(32) CHECK (value LIKE \'test%\')',
                ],
            ],
            [
                Schema::TYPE_STRING . '(32)',
                $this->string(32),
                [
                    'mysql' => 'varchar(32)',
                    'postgres' => 'varchar(32)',
                    'sqlite' => 'varchar(32)',
                    'oci' => 'VARCHAR2(32)',
                    'sqlsrv' => 'nvarchar(32)',
                ],
            ],
            [
                Schema::TYPE_STRING,
                $this->string(),
                [
                    'mysql' => 'varchar(255)',
                    'postgres' => 'varchar(255)',
                    'sqlite' => 'varchar(255)',
                    'oci' => 'VARCHAR2(255)',
                    'sqlsrv' => 'nvarchar(255)',
                ],
            ],
            [
                Schema::TYPE_TEXT . " CHECK (value LIKE 'test%')",
                $this->text()->check("value LIKE 'test%'"),
                [
                    'mysql' => "text CHECK (value LIKE 'test%')",
                    'sqlite' => "text CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(max) CHECK (value LIKE 'test%')",
                ],
            ],
            [
                Schema::TYPE_TEXT . ' CHECK (value LIKE \'test%\')',
                $this->text()->check('value LIKE \'test%\''),
                [
                    'postgres' => 'text CHECK (value LIKE \'test%\')',
                    'oci' => 'CLOB CHECK (value LIKE \'test%\')',
                ],
            ],
            [
                Schema::TYPE_TEXT . ' NOT NULL',
                $this->text()->notNull(),
                [
                    'mysql' => 'text NOT NULL',
                    'postgres' => 'text NOT NULL',
                    'sqlite' => 'text NOT NULL',
                    'oci' => 'CLOB NOT NULL',
                    'sqlsrv' => 'nvarchar(max) NOT NULL',
                ],
            ],
            [
                Schema::TYPE_TEXT . " CHECK (value LIKE 'test%')",
                $this->text()->check("value LIKE 'test%'"),
                [
                    'mysql' => "text CHECK (value LIKE 'test%')",
                    'sqlite' => "text CHECK (value LIKE 'test%')",
                    'sqlsrv' => "nvarchar(max) CHECK (value LIKE 'test%')",
                ],
                Schema::TYPE_TEXT . " CHECK (value LIKE 'test%')",
            ],
            [
                Schema::TYPE_TEXT . ' CHECK (value LIKE \'test%\')',
                $this->text()->check('value LIKE \'test%\''),
                [
                    'postgres' => 'text CHECK (value LIKE \'test%\')',
                    'oci' => 'CLOB CHECK (value LIKE \'test%\')',
                ],
                Schema::TYPE_TEXT . ' CHECK (value LIKE \'test%\')',
            ],
            [
                Schema::TYPE_TEXT . ' NOT NULL',
                $this->text()->notNull(),
                [
                    'mysql' => 'text NOT NULL',
                    'postgres' => 'text NOT NULL',
                    'sqlite' => 'text NOT NULL',
                    'oci' => 'CLOB NOT NULL',
                    'sqlsrv' => 'nvarchar(max) NOT NULL',
                ],
                Schema::TYPE_TEXT . ' NOT NULL',
            ],
            [
                Schema::TYPE_TEXT,
                $this->text(),
                [
                    'mysql' => 'text',
                    'postgres' => 'text',
                    'sqlite' => 'text',
                    'oci' => 'CLOB',
                    'sqlsrv' => 'nvarchar(max)',
                ],
                Schema::TYPE_TEXT,
            ],
            [
                Schema::TYPE_TEXT,
                $this->text(),
                [
                    'mysql' => 'text',
                    'postgres' => 'text',
                    'sqlite' => 'text',
                    'oci' => 'CLOB',
                    'sqlsrv' => 'nvarchar(max)',
                ],
            ],
            [
                Schema::TYPE_TIME . ' NOT NULL',
                $this->time()->notNull(),
                [
                    'postgres' => 'time(0) NOT NULL',
                    'sqlite' => 'time NOT NULL',
                    'oci' => 'TIMESTAMP NOT NULL',
                    'sqlsrv' => 'time NOT NULL',
                ],
            ],
            [
                Schema::TYPE_TIME,
                $this->time(),
                [
                    'postgres' => 'time(0)',
                    'sqlite' => 'time',
                    'oci' => 'TIMESTAMP',
                    'sqlsrv' => 'time',
                ],
            ],
            [
                Schema::TYPE_TIMESTAMP . ' NOT NULL',
                $this->timestamp()->notNull(),
                [
                    'postgres' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'timestamp NOT NULL',
                    'oci' => 'TIMESTAMP NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            [
                Schema::TYPE_TIMESTAMP,
                $this->timestamp(),
                [
                    /**
                     * MySQL has its own TIMESTAMP test realization.
                     *
                     * {@see QueryBuilderTest::columnTypes()}
                     */
                    'postgres' => 'timestamp(0)',
                    'sqlite' => 'timestamp',
                    'oci' => 'TIMESTAMP',
                    'sqlsrv' => 'datetime',
                ],
            ],
            [
                Schema::TYPE_TIMESTAMP . ' NULL DEFAULT NULL',
                $this->timestamp()->defaultValue(null),
                [
                    'postgres' => 'timestamp(0) NULL DEFAULT NULL',
                    'sqlite' => 'timestamp NULL DEFAULT NULL',
                    'sqlsrv' => 'datetime NULL DEFAULT NULL',
                ],
            ],
            [
                Schema::TYPE_UPK,
                $this->primaryKey()->unsigned(),
                [
                    'mysql' => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'postgres' => 'serial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            [
                Schema::TYPE_UBIGPK,
                $this->bigPrimaryKey()->unsigned(),
                [
                    'mysql' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'postgres' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            [
                Schema::TYPE_INTEGER . " COMMENT 'test comment'",
                $this->integer()->comment('test comment'),
                [
                    'mysql' => "int(11) COMMENT 'test comment'",
                    'postgres' => 'integer',
                    'sqlsrv' => 'int',
                ],
                [
                    'sqlsrv' => 'integer',
                ]
            ],
            [
                Schema::TYPE_PK . " COMMENT 'test comment'",
                $this->primaryKey()->comment('test comment'),
                [
                    'mysql' => "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'test comment'",
                    'postgres' => 'serial NOT NULL PRIMARY KEY',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
                [
                    'sqlsrv' => 'pk',
                ]
            ],
            [
                Schema::TYPE_PK . ' FIRST',
                $this->primaryKey()->first(),
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
                    'postgres' => 'serial NOT NULL PRIMARY KEY',
                    'oci' => 'NUMBER(10) NOT NULL PRIMARY KEY',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
                [
                    'sqlsrv' => 'pk',
                ]
            ],
            [
                Schema::TYPE_INTEGER . ' FIRST',
                $this->integer()->first(),
                [
                    'mysql' => 'int(11) FIRST',
                    'postgres' => 'integer',
                    'oci' => 'NUMBER(10)',
                    'sqlsrv' => 'int',
                ],
                [
                    'sqlsrv' => 'integer',
                ]
            ],
            [
                Schema::TYPE_STRING . ' FIRST',
                $this->string()->first(),
                [
                    'mysql' => 'varchar(255) FIRST',
                    'postgres' => 'varchar(255)',
                    'oci' => 'VARCHAR2(255)',
                    'sqlsrv' => 'nvarchar(255)',
                ],
                [
                    'sqlsrv' => 'string',
                ]
            ],
            [
                Schema::TYPE_INTEGER . ' NOT NULL FIRST',
                $this->integer()->append('NOT NULL')->first(),
                [
                    'mysql' => 'int(11) NOT NULL FIRST',
                    'postgres' => 'integer NOT NULL',
                    'oci' => 'NUMBER(10) NOT NULL',
                    'sqlsrv' => 'int NOT NULL',
                ],
                [
                    'sqlsrv' => 'integer NOT NULL',
                ]
            ],
            [
                Schema::TYPE_STRING . ' NOT NULL FIRST',
                $this->string()->append('NOT NULL')->first(),
                [
                    'mysql' => 'varchar(255) NOT NULL FIRST',
                    'postgres' => 'varchar(255) NOT NULL',
                    'oci' => 'VARCHAR2(255) NOT NULL',
                    'sqlsrv' => 'nvarchar(255) NOT NULL',
                ],
                [
                    'sqlsrv' => 'string NOT NULL',
                ]
            ],
        ];

        $db =  $this->getConnection();

        foreach ($items as $i => $item) {
            if (array_key_exists($db->getDriverName(), $item[2])) {
                $item[2] = $item[2][$db->getDriverName()];
                $items[$i] = $item;
            } else {
                unset($items[$i]);
            }
        }

        return array_values($items);
    }

    public function testGetColumnType(): void
    {
        $db =  $this->getConnection();

        $qb = $this->getQueryBuilder();

        foreach ($this->columnTypes() as $item) {
            [$column, $builder, $expected] = $item;

            if (isset($item[3][$db->getDriverName()])) {
                $expectedColumnSchemaBuilder = $item[3][$db->getDriverName()];
            } elseif (isset($item[3]) && !is_array($item[3])) {
                $expectedColumnSchemaBuilder = $item[3];
            } else {
                $expectedColumnSchemaBuilder = $column;
            }

            $this->assertEquals($expected, $qb->getColumnType($column));
            $this->assertEquals($expected, $qb->getColumnType($builder));
            $this->assertEquals($expectedColumnSchemaBuilder, $builder->__toString());
        }
    }

    public function testCreateTableColumnTypes(): void
    {
        $qb = $this->getQueryBuilder();

        if ($qb->getDb()->getTableSchema('column_type_table', true) !== null) {
            $this->getConnection(false)->createCommand($qb->dropTable('column_type_table'))->execute();
        }

        $columns = [];
        $i = 0;

        foreach ($this->columnTypes() as [$column, $builder, $expected]) {
            if (
                !(
                    strncmp($column, Schema::TYPE_PK, 2) === 0 ||
                    strncmp($column, Schema::TYPE_UPK, 3) === 0 ||
                    strncmp($column, Schema::TYPE_BIGPK, 5) === 0 ||
                    strncmp($column, Schema::TYPE_UBIGPK, 6) === 0 ||
                    strncmp(substr($column, -5), 'FIRST', 5) === 0
                )
            ) {
                $columns['col' . ++$i] = str_replace('CHECK (value', 'CHECK ([[col' . $i . ']]', $column);
            }
        }

        $this->getConnection(false)->createCommand($qb->createTable('column_type_table', $columns))->execute();

        $this->assertNotEmpty($qb->getDb()->getTableSchema('column_type_table', true));
    }

    public function conditionProviderTrait(): array
    {
        $db = $this->getConnection();

        $conditions = [
            /* empty values */
            [['like', 'name', []], '0=1', []],
            [['not like', 'name', []], '', []],
            [['or like', 'name', []], '0=1', []],
            [['or not like', 'name', []], '', []],

            /* not */
            [['not', 'name'], 'NOT (name)', []],
            [
                ['not', (new Query($db))->select('exists')->from('some_table')],
                'NOT ((SELECT [[exists]] FROM [[some_table]]))', []
            ],

            /* and */
            [['and', 'id=1', 'id=2'], '(id=1) AND (id=2)', []],
            [['and', 'type=1', ['or', 'id=1', 'id=2']], '(type=1) AND ((id=1) OR (id=2))', []],
            [['and', 'id=1', new Expression('id=:qp0', [':qp0' => 2])], '(id=1) AND (id=:qp0)', [':qp0' => 2]],
            [
                ['and', ['expired' => false], (new Query($db))->select('count(*) > 1')->from('queue')],
                '([[expired]]=:qp0) AND ((SELECT count(*) > 1 FROM [[queue]]))', [':qp0' => false]
            ],

            /* or */
            [['or', 'id=1', 'id=2'], '(id=1) OR (id=2)', []],
            [['or', 'type=1', ['or', 'id=1', 'id=2']], '(type=1) OR ((id=1) OR (id=2))', []],
            [['or', 'type=1', new Expression('id=:qp0', [':qp0' => 1])], '(type=1) OR (id=:qp0)', [':qp0' => 1]],

            /* between */
            [['between', 'id', 1, 10], '[[id]] BETWEEN :qp0 AND :qp1', [':qp0' => 1, ':qp1' => 10]],
            [['not between', 'id', 1, 10], '[[id]] NOT BETWEEN :qp0 AND :qp1', [':qp0' => 1, ':qp1' => 10]],
            [
                ['between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), new Expression('NOW()')],
                '[[date]] BETWEEN (NOW() - INTERVAL 1 MONTH) AND NOW()',
                []
            ],
            [
                ['between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), 123],
                '[[date]] BETWEEN (NOW() - INTERVAL 1 MONTH) AND :qp0',
                [':qp0' => 123]
            ],
            [
                ['not between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), new Expression('NOW()')],
                '[[date]] NOT BETWEEN (NOW() - INTERVAL 1 MONTH) AND NOW()',
                []
            ],
            [
                ['not between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), 123],
                '[[date]] NOT BETWEEN (NOW() - INTERVAL 1 MONTH) AND :qp0',
                [':qp0' => 123]
            ],
            [
                new BetweenColumnsCondition('2018-02-11', 'BETWEEN', 'create_time', 'update_time'),
                ':qp0 BETWEEN [[create_time]] AND [[update_time]]',
                [':qp0' => '2018-02-11']
            ],
            [
                new BetweenColumnsCondition('2018-02-11', 'NOT BETWEEN', 'NOW()', 'update_time'),
                ':qp0 NOT BETWEEN NOW() AND [[update_time]]',
                [':qp0' => '2018-02-11']
            ],
            [
                new BetweenColumnsCondition(new Expression('NOW()'), 'BETWEEN', 'create_time', 'update_time'),
                'NOW() BETWEEN [[create_time]] AND [[update_time]]',
                []
            ],
            [
                new BetweenColumnsCondition(new Expression('NOW()'), 'NOT BETWEEN', 'create_time', 'update_time'),
                'NOW() NOT BETWEEN [[create_time]] AND [[update_time]]',
                []
            ],
            [
                new BetweenColumnsCondition(
                    new Expression('NOW()'),
                    'NOT BETWEEN',
                    (new Query($db))->select('min_date')->from('some_table'),
                    'max_date'
                ),
                'NOW() NOT BETWEEN (SELECT [[min_date]] FROM [[some_table]]) AND [[max_date]]',
                []
            ],

            /* in */
            [
                ['in', 'id', [1, 2, (new Query($db))->select('three')->from('digits')]],
                '[[id]] IN (:qp0, :qp1, (SELECT [[three]] FROM [[digits]]))', [':qp0' => 1, ':qp1' => 2]
            ],
            [['not in', 'id', [1, 2, 3]], '[[id]] NOT IN (:qp0, :qp1, :qp2)', [':qp0' => 1, ':qp1' => 2, ':qp2' => 3]],
            [
                ['in', 'id', (new Query($db))->select('id')->from('users')->where(['active' => 1])],
                '[[id]] IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', [':qp0' => 1]
            ],
            [
                ['not in', 'id', (new Query($db))->select('id')->from('users')->where(['active' => 1])],
                '[[id]] NOT IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', [':qp0' => 1]
            ],

            [['in', 'id', 1],   '[[id]]=:qp0', [':qp0' => 1]],
            [['in', 'id', [1]], '[[id]]=:qp0', [':qp0' => 1]],
            [['in', 'id', new TraversableObject([1])], '[[id]]=:qp0', [':qp0' => 1]],
            'composite in' => [
                ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
                '([[id]], [[name]]) IN ((:qp0, :qp1))',
                [':qp0' => 1, ':qp1' => 'oy'],
            ],

            /* in using array objects. */
            [['id' => new TraversableObject([1, 2])], '[[id]] IN (:qp0, :qp1)', [':qp0' => 1, ':qp1' => 2]],

            [
                ['in', 'id', new TraversableObject([1, 2, 3])],
                '[[id]] IN (:qp0, :qp1, :qp2)', [':qp0' => 1, ':qp1' => 2, ':qp2' => 3]
            ],

            'composite in using array objects' => [
                ['in', new TraversableObject(['id', 'name']), new TraversableObject([
                    ['id' => 1, 'name' => 'oy'],
                    ['id' => 2, 'name' => 'yo'],
                ])],
                '([[id]], [[name]]) IN ((:qp0, :qp1), (:qp2, :qp3))',
                [':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2, ':qp3' => 'yo'],
            ],

            /* exists */
            [
                ['exists', (new Query($db))->select('id')->from('users')->where(['active' => 1])],
                'EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', [':qp0' => 1]
            ],
            [
                ['not exists', (new Query($db))->select('id')->from('users')->where(['active' => 1])],
                'NOT EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', [':qp0' => 1]
            ],

            /* simple conditions */
            [['=', 'a', 'b'], '[[a]] = :qp0', [':qp0' => 'b']],
            [['>', 'a', 1], '[[a]] > :qp0', [':qp0' => 1]],
            [['>=', 'a', 'b'], '[[a]] >= :qp0', [':qp0' => 'b']],
            [['<', 'a', 2], '[[a]] < :qp0', [':qp0' => 2]],
            [['<=', 'a', 'b'], '[[a]] <= :qp0', [':qp0' => 'b']],
            [['<>', 'a', 3], '[[a]] <> :qp0', [':qp0' => 3]],
            [['!=', 'a', 'b'], '[[a]] != :qp0', [':qp0' => 'b']],
            [
                ['>=', 'date', new Expression('DATE_SUB(NOW(), INTERVAL 1 MONTH)')],
                '[[date]] >= DATE_SUB(NOW(), INTERVAL 1 MONTH)', []
            ],
            [
                ['>=', 'date', new Expression('DATE_SUB(NOW(), INTERVAL :month MONTH)', [':month' => 2])],
                '[[date]] >= DATE_SUB(NOW(), INTERVAL :month MONTH)', [':month' => 2]
            ],
            [
                ['=', 'date', (new Query($db))->select('max(date)')->from('test')->where(['id' => 5])],
                '[[date]] = (SELECT max(date) FROM [[test]] WHERE [[id]]=:qp0)', [':qp0' => 5]
            ],

            /* hash condition */
            [['a' => 1, 'b' => 2], '([[a]]=:qp0) AND ([[b]]=:qp1)', [':qp0' => 1, ':qp1' => 2]],
            [
                ['a' => new Expression('CONCAT(col1, col2)'), 'b' => 2],
                '([[a]]=CONCAT(col1, col2)) AND ([[b]]=:qp0)', [':qp0' => 2]
            ],

            /* direct conditions */
            ['a = CONCAT(col1, col2)', 'a = CONCAT(col1, col2)', []],
            [
                new Expression('a = CONCAT(col1, :param1)', ['param1' => 'value1']),
                'a = CONCAT(col1, :param1)', ['param1' => 'value1']
            ],

            /* Expression with params as operand of 'not' */
            [['not', new Expression('any_expression(:a)', [':a' => 1])], 'NOT (any_expression(:a))', [':a' => 1]],
            [new Expression('NOT (any_expression(:a))', [':a' => 1]), 'NOT (any_expression(:a))', [':a' => 1]],
        ];

        switch ($db->getDriverName()) {
            case 'sqlsrv':
            case 'sqlite':
                $conditions = array_merge($conditions, [
                    [
                        ['in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                        '(([[id]] = :qp0 AND [[name]] = :qp1) OR ([[id]] = :qp2 AND [[name]] = :qp3))',
                        [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar']
                    ],
                    [
                        ['not in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                        '(([[id]] != :qp0 OR [[name]] != :qp1) AND ([[id]] != :qp2 OR [[name]] != :qp3))',
                        [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar']
                    ],
                    //[['in', ['id', 'name'], (new Query())->select(['id', 'name'])->from('users')->where(['active' => 1])], 'EXISTS (SELECT 1 FROM (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0) AS a WHERE a.[[id]] = [[id AND a.]]name[[ = ]]name`)', [':qp0' => 1] ],
                    //[['not in', ['id', 'name'], (new Query())->select(['id', 'name'])->from('users')->where(['active' => 1])], 'NOT EXISTS (SELECT 1 FROM (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0) AS a WHERE a.[[id]] = [[id]] AND a.[[name = ]]name`)', [':qp0' => 1] ],
                ]);

                break;
            default:
                $conditions = array_merge($conditions, [
                    [
                        ['in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                        '([[id]], [[name]]) IN ((:qp0, :qp1), (:qp2, :qp3))',
                        [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar']
                    ],
                    [
                        ['not in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                        '([[id]], [[name]]) NOT IN ((:qp0, :qp1), (:qp2, :qp3))',
                        [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar']
                    ],
                    [
                        [
                            'in',
                            ['id', 'name'],
                            (new Query())->select(['id', 'name'])->from('users')->where(['active' => 1])
                        ],
                        '([[id]], [[name]]) IN (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0)', [':qp0' => 1]
                    ],
                    [
                        [
                            'not in',
                            ['id', 'name'],
                            (new Query())->select(['id', 'name'])->from('users')->where(['active' => 1])
                        ],
                        '([[id]], [[name]]) NOT IN (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0)',
                        [':qp0' => 1]
                    ],
                ]);

                break;
        }

        /* adjust dbms specific escaping */
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = $this->replaceQuotes($condition[1]);
        }

        return $conditions;
    }

    /**
     * @dataProvider conditionProvider
     *
     * @param ExpressionInterface|array $condition
     * @param string $expected
     * @param array $expectedParams
     */
    public function testBuildCondition($condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->where($condition);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    public function filterConditionProvider(): array
    {
        $conditions = [
            /* like */
            [['like', 'name', []], '', []],
            [['not like', 'name', []], '', []],
            [['or like', 'name', []], '', []],
            [['or not like', 'name', []], '', []],

            /* not */
            [['not', ''], '', []],

            /* and */
            [['and', '', ''], '', []],
            [['and', '', 'id=2'], 'id=2', []],
            [['and', 'id=1', ''], 'id=1', []],
            [['and', 'type=1', ['or', '', 'id=2']], '(type=1) AND (id=2)', []],

            /* or */
            [['or', 'id=1', ''], 'id=1', []],
            [['or', 'type=1', ['or', '', 'id=2']], '(type=1) OR (id=2)', []],

            /* between */
            [['between', 'id', 1, null], '', []],
            [['not between', 'id', null, 10], '', []],

            /* in */
            [['in', 'id', []], '', []],
            [['not in', 'id', []], '', []],

            /* simple conditions */
            [['=', 'a', ''], '', []],
            [['>', 'a', ''], '', []],
            [['>=', 'a', ''], '', []],
            [['<', 'a', ''], '', []],
            [['<=', 'a', ''], '', []],
            [['<>', 'a', ''], '', []],
            [['!=', 'a', ''], '', []],
        ];

        /* adjust dbms specific escaping */
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = $this->replaceQuotes($condition[1]);
        }

        return $conditions;
    }

    /**
     * @dataProvider filterConditionProvider
     *
     * @param array $condition
     * @param string $expected
     * @param array $expectedParams
     */
    public function testBuildFilterCondition(array $condition, string $expected, array $expectedParams): void
    {
        $query = (new Query($this->getConnection()))->filterWhere($condition);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    public function buildFromDataProviderTrait(): array
    {
        return [
            ['test t1', '[[test]] [[t1]]'],
            ['test as t1', '[[test]] [[t1]]'],
            ['test AS t1', '[[test]] [[t1]]'],
            ['test', '[[test]]'],
        ];
    }

    /**
     * @dataProvider buildFromDataProvider
     *
     * @param string $table
     * @param string $expected
     *
     * @throws Exception
     */
    public function testBuildFrom(string $table, string $expected): void
    {
        $params = [];

        $sql = $this->getQueryBuilder()->buildFrom([$table], $params);

        $this->assertEquals('FROM ' . $this->replaceQuotes($expected), $sql);
    }

    public function primaryKeysProvider(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_pk';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->dropPrimaryKey($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]])",
                static function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->addPrimaryKey($name, $tableName, 'C_id_1');
                },
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]], [[C_id_2]])",
                static function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->addPrimaryKey($name, $tableName, 'C_id_1, C_id_2');
                },
            ],
        ];
    }

    /**
     * @dataProvider primaryKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropPrimaryKey(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection(false)->quoteSql($sql), $builder($this->getQueryBuilder(false)));
    }

    public function foreignKeysProvider(): array
    {
        $tableName = 'T_constraints_3';
        $name = 'CN_constraints_3';
        $pkTableName = 'T_constraints_2';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->dropForeignKey($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]])"
                . " REFERENCES {{{$pkTableName}}} ([[C_id_1]]) ON DELETE CASCADE ON UPDATE CASCADE",
                static function (QueryBuilder $qb) use ($tableName, $name, $pkTableName) {
                    return $qb->addForeignKey(
                        $name,
                        $tableName,
                        'C_fk_id_1',
                        $pkTableName,
                        'C_id_1',
                        'CASCADE',
                        'CASCADE'
                    );
                },
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]], [[C_fk_id_2]])"
                . " REFERENCES {{{$pkTableName}}} ([[C_id_1]], [[C_id_2]]) ON DELETE CASCADE ON UPDATE CASCADE",
                static function (QueryBuilder $qb) use ($tableName, $name, $pkTableName) {
                    return $qb->addForeignKey(
                        $name,
                        $tableName,
                        'C_fk_id_1, C_fk_id_2',
                        $pkTableName,
                        'C_id_1, C_id_2',
                        'CASCADE',
                        'CASCADE'
                    );
                },
            ],
        ];
    }

    /**
     * @dataProvider foreignKeysProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropForeignKey(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection(false)->quoteSql($sql), $builder($this->getQueryBuilder(false)));
    }

    public function indexesProvider(): array
    {
        $tableName = 'T_constraints_2';
        $name1 = 'CN_constraints_2_single';
        $name2 = 'CN_constraints_2_multi';

        return [
            'drop' => [
                "DROP INDEX [[$name1]] ON {{{$tableName}}}",
                static function (QueryBuilder $qb) use ($tableName, $name1) {
                    return $qb->dropIndex($name1, $tableName);
                },
            ],
            'create' => [
                "CREATE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])",
                static function (QueryBuilder $qb) use ($tableName, $name1) {
                    return $qb->createIndex($name1, $tableName, 'C_index_1');
                },
            ],
            'create (2 columns)' => [
                "CREATE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])",
                static function (QueryBuilder $qb) use ($tableName, $name2) {
                    return $qb->createIndex($name2, $tableName, 'C_index_2_1, C_index_2_2');
                },
            ],
            'create unique' => [
                "CREATE UNIQUE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])",
                static function (QueryBuilder $qb) use ($tableName, $name1) {
                    return $qb->createIndex($name1, $tableName, 'C_index_1', true);
                },
            ],
            'create unique (2 columns)' => [
                "CREATE UNIQUE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])",
                static function (QueryBuilder $qb) use ($tableName, $name2) {
                    return $qb->createIndex($name2, $tableName, 'C_index_2_1, C_index_2_2', true);
                },
            ],
        ];
    }

    /**
     * @dataProvider indexesProvider
     *
     * @param string $sql
     */
    public function testCreateDropIndex(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection(false)->quoteSql($sql), $builder($this->getQueryBuilder(false)));
    }

    public function uniquesProvider(): array
    {
        $tableName1 = 'T_constraints_1';
        $name1 = 'CN_unique';
        $tableName2 = 'T_constraints_2';
        $name2 = 'CN_constraints_2_multi';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName1}}} DROP CONSTRAINT [[$name1]]",
                static function (QueryBuilder $qb) use ($tableName1, $name1) {
                    return $qb->dropUnique($name1, $tableName1);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName1}}} ADD CONSTRAINT [[$name1]] UNIQUE ([[C_unique]])",
                static function (QueryBuilder $qb) use ($tableName1, $name1) {
                    return $qb->addUnique($name1, $tableName1, 'C_unique');
                },
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName2}}} ADD CONSTRAINT [[$name2]] UNIQUE ([[C_index_2_1]], [[C_index_2_2]])",
                static function (QueryBuilder $qb) use ($tableName2, $name2) {
                    return $qb->addUnique($name2, $tableName2, 'C_index_2_1, C_index_2_2');
                },
            ],
        ];
    }

    /**
     * @dataProvider uniquesProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropUnique(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection(false)->quoteSql($sql), $builder($this->getQueryBuilder(false)));
    }

    public function checksProvider(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_check';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->dropCheck($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] CHECK ([[C_not_null]] > 100)",
                static function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->addCheck($name, $tableName, '[[C_not_null]] > 100');
                },
            ],
        ];
    }

    /**
     * @dataProvider checksProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropCheck(string $sql, Closure $builder): void
    {
        $this->assertSame($this->getConnection(false)->quoteSql($sql), $builder($this->getQueryBuilder(false)));
    }

    public function defaultValuesProvider(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_default';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->dropDefaultValue($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] DEFAULT 0 FOR [[C_default]]",
                static function (QueryBuilder $qb) use ($tableName, $name) {
                    return $qb->addDefaultValue($name, $tableName, 'C_default', 0);
                },
            ],
        ];
    }

    /**
     * @dataProvider defaultValuesProvider
     *
     * @param string $sql
     * @param Closure $builder
     */
    public function testAddDropDefaultValue(string $sql, Closure $builder): void
    {
        $this->assertSame(
            $this->getConnection(false)->quoteSql($sql),
            $builder($this->getQueryBuilder(false))
        );
    }

    public function existsParamsProvider(): array
    {
        return [
            [
                'exists',
                $this->replaceQuotes(
                    'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE EXISTS (SELECT [[1]] FROM [[Website]] [[w]])'
                )
            ],
            [
                'not exists',
                $this->replaceQuotes(
                    'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE NOT EXISTS (SELECT [[1]] FROM [[Website]] [[w]])'
                )
            ],
        ];
    }

    /**
     * @dataProvider existsParamsProvider
     *
     * @param string $cond
     * @param string $expectedQuerySql
     */
    public function testBuildWhereExists(string $cond, string $expectedQuerySql): void
    {
        $db = $this->getConnection();

        $expectedQueryParams = [];

        $subQuery = new Query($db);

        $subQuery->select('1')
            ->from('Website w');

        $query = new Query($db);

        $query->select('id')
            ->from('TotalExample t')
            ->where([$cond, $subQuery]);

        [$actualQuerySql, $actualQueryParams] = $this->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $actualQueryParams);
    }

    public function testBuildWhereExistsWithParameters(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]]'
            . ' WHERE (w.id = t.website_id) AND (w.merchant_id = :merchant_id))) AND (t.some_column = :some_value)'
        );

        $expectedQueryParams = [':some_value' => 'asd', ':merchant_id' => 6];

        $subQuery = new Query($db);

        $subQuery->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere('w.merchant_id = :merchant_id', [':merchant_id' => 6]);

        $query = new Query($db);

        $query->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere('t.some_column = :some_value', [':some_value' => 'asd']);

        [$actualQuerySql, $queryParams] = $this->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $queryParams);
    }

    public function testBuildWhereExistsWithArrayParameters(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE (EXISTS (SELECT [[1]] FROM [[Website]] [[w]]'
            . ' WHERE (w.id = t.website_id) AND (([[w]].[[merchant_id]]=:qp0) AND ([[w]].[[user_id]]=:qp1))))'
            . ' AND ([[t]].[[some_column]]=:qp2)'
        );

        $expectedQueryParams = [':qp0' => 6, ':qp1' => 210, ':qp2' => 'asd'];

        $subQuery = new Query($db);

        $subQuery->select('1')
            ->from('Website w')
            ->where('w.id = t.website_id')
            ->andWhere(['w.merchant_id' => 6, 'w.user_id' => '210']);

        $query = new Query($db);

        $query->select('id')
            ->from('TotalExample t')
            ->where(['exists', $subQuery])
            ->andWhere(['t.some_column' => 'asd']);

        [$actualQuerySql, $queryParams] = $this->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals($expectedQueryParams, $queryParams);
    }

    /**
     * This test contains three select queries connected with UNION and UNION ALL constructions.
     * It could be useful to use "phpunit --group=db --filter testBuildUnion" command for run it.
     */
    public function testBuildUnion(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            '(SELECT [[id]] FROM [[TotalExample]] [[t1]] WHERE (w > 0) AND (x < 2)) UNION ( SELECT [[id]]'
            . ' FROM [[TotalTotalExample]] [[t2]] WHERE w > 5 ) UNION ALL ( SELECT [[id]] FROM [[TotalTotalExample]]'
            . ' [[t3]] WHERE w = 3 )'
        );

        $query = new Query($db);
        $secondQuery = new Query($db);

        $secondQuery->select('id')
              ->from('TotalTotalExample t2')
              ->where('w > 5');

        $thirdQuery = new Query($db);

        $thirdQuery->select('id')
              ->from('TotalTotalExample t3')
              ->where('w = 3');

        $query->select('id')
              ->from('TotalExample t1')
              ->where(['and', 'w > 0', 'x < 2'])
              ->union($secondQuery)
              ->union($thirdQuery, true);

        [$actualQuerySql, $queryParams] = $this->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals([], $queryParams);
    }

    public function testBuildWithQuery(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            'WITH a1 AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1), a2 AS ((SELECT [[id]] FROM [[t2]]'
            . ' INNER JOIN [[a1]] ON t2.id = a1.id WHERE expr = 2) UNION ( SELECT [[id]] FROM [[t3]] WHERE expr = 3 ))'
            . ' SELECT * FROM [[a2]]'
        );

        $with1Query = (new Query($db))
            ->select('id')
            ->from('t1')
            ->where('expr = 1');

        $with2Query = (new Query($db))
            ->select('id')
            ->from('t2')
            ->innerJoin('a1', 't2.id = a1.id')
            ->where('expr = 2');

        $with3Query = (new Query($db))
            ->select('id')
            ->from('t3')
            ->where('expr = 3');

        $query = (new Query($db))
            ->withQuery($with1Query, 'a1')
            ->withQuery($with2Query->union($with3Query), 'a2')
            ->from('a2');

        [$actualQuerySql, $queryParams] = $this->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals([], $queryParams);
    }

    public function testBuildWithQueryRecursive(): void
    {
        $db = $this->getConnection();

        $expectedQuerySql = $this->replaceQuotes(
            'WITH RECURSIVE a1 AS (SELECT [[id]] FROM [[t1]] WHERE expr = 1) SELECT * FROM [[a1]]'
        );

        $with1Query = (new Query($db))
            ->select('id')
            ->from('t1')
            ->where('expr = 1');

        $query = (new Query($db))
            ->withQuery($with1Query, 'a1', true)
            ->from('a1');

        [$actualQuerySql, $queryParams] = $this->getQueryBuilder()->build($query);

        $this->assertEquals($expectedQuerySql, $actualQuerySql);
        $this->assertEquals([], $queryParams);
    }

    public function testSelectSubquery(): void
    {
        $db = $this->getConnection();

        $subquery = (new Query($db))
            ->select('COUNT(*)')
            ->from('operations')
            ->where('account_id = accounts.id');

        $query = (new Query($db))
            ->select('*')
            ->from('accounts')
            ->addSelect(['operations_count' => $subquery]);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT *, (SELECT COUNT(*) FROM [[operations]] WHERE account_id = accounts.id) AS [[operations_count]]'
            . ' FROM [[accounts]]'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testComplexSelect(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))
            ->select([
                'ID' => 't.id',
                'gsm.username as GSM',
                'part.Part',
                'Part Cost' => 't.Part_Cost',
                'st_x(location::geometry) as lon',
                new Expression(
                    $this->replaceQuotes(
                        "case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action'"
                        . " END as [[Next Action]]"
                    )
                ),
            ])
            ->from('tablename');

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT [[t]].[[id]] AS [[ID]], [[gsm]].[[username]] AS [[GSM]], [[part]].[[Part]], [[t]].[[Part_Cost]]'
            . ' AS [[Part Cost]], st_x(location::geometry) AS [[lon]], case t.Status_Id when 1 then \'Acknowledge\''
            . ' when 2 then \'No Action\' else \'Unknown Action\' END as [[Next Action]] FROM [[tablename]]'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testSelectExpression(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))
            ->select(new Expression('1 AS ab'))
            ->from('tablename');

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT 1 AS ab FROM [[tablename]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('1 AS ab'))
            ->addSelect(new Expression('2 AS cd'))
            ->addSelect(['ef' => new Expression('3')])
            ->from('tablename');

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT 1 AS ab, 2 AS cd, 3 AS [[ef]] FROM [[tablename]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('SUBSTR(name, 0, :len)', [':len' => 4]))
            ->from('tablename');

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT SUBSTR(name, 0, :len) FROM [[tablename]]');

        $this->assertEquals($expected, $sql);
        $this->assertEquals([':len' => 4], $params);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/10869}
     */
    public function testFromIndexHint(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from([new Expression('{{%user}} USE INDEX (primary)')]);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM {{%user}} USE INDEX (primary)');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->from([new Expression('{{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1)')])
            ->leftJoin(['p' => 'profile'], 'user.id = profile.user_id USE INDEX (i2)');

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM {{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1)'
            . ' LEFT JOIN [[profile]] [[p]] ON user.id = profile.user_id USE INDEX (i2)'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testFromSubquery(): void
    {
        $db = $this->getConnection();

        /* query subquery */
        $subquery = (new Query($db))->from('user')->where('account_id = accounts.id');

        $query = (new Query($db))->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = accounts.id) [[activeusers]]'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* query subquery with params */
        $subquery = (new Query($db))->from('user')->where('account_id = :id', ['id' => 1]);

        $query = (new Query($db))->from(['activeusers' => $subquery])->where('abc = :abc', ['abc' => 'abc']);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = :id) [[activeusers]] WHERE abc = :abc'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEquals(['id'  => 1, 'abc' => 'abc'], $params);

        /* simple subquery */
        $subquery = '(SELECT * FROM user WHERE account_id = accounts.id)';

        $query = (new Query($db))->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM (SELECT * FROM user WHERE account_id = accounts.id) [[activeusers]]'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);
    }

    public function testOrderBy(): void
    {
        $db = $this->getConnection();

        /* simple string */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->orderBy('name ASC, date DESC');

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->orderBy(['name' => SORT_ASC, 'date' => SORT_DESC]);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->orderBy(new Expression('SUBSTR(name, 3, 4) DESC, x ASC'));

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM [[operations]] WHERE account_id = accounts.id ORDER BY SUBSTR(name, 3, 4) DESC, x ASC'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression with params */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->orderBy(new Expression('SUBSTR(name, 3, :to) DESC, x ASC', [':to' => 4]));

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] ORDER BY SUBSTR(name, 3, :to) DESC, x ASC');

        $this->assertEquals($expected, $sql);
        $this->assertEquals([':to' => 4], $params);
    }

    public function testGroupBy(): void
    {
        $db = $this->getConnection();

        /* simple string */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->groupBy('name, date');

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->groupBy(['name', 'date']);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->groupBy(new Expression('SUBSTR(name, 0, 1), x'));

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM [[operations]] WHERE account_id = accounts.id GROUP BY SUBSTR(name, 0, 1), x'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression with params */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->groupBy(new Expression('SUBSTR(name, 0, :to), x', [':to' => 4]));

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] GROUP BY SUBSTR(name, 0, :to), x');

        $this->assertEquals($expected, $sql);
        $this->assertEquals([':to' => 4], $params);
    }

    /**
     * @dataProvider insertProvider
     *
     * @param string $table
     * @param ColumnSchema|array $columns
     * @param array $params
     * @param string $expectedSQL
     * @param array $expectedParams
     */
    public function testInsert(string $table, $columns, array $params, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = $params;

        $actualSQL = $this->getQueryBuilder()->insert($table, $columns, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    /**
     * Dummy test to speed up QB's tests which rely on DB schema.
     */
    public function testInitFixtures(): void
    {
        $this->assertInstanceOf(QueryBuilder::class, $this->getQueryBuilder(true, true));
    }

    public function upsertProviderTrait(): array
    {
        $db = $this->getConnection();

        return [
            'regular values' => [
                'T_upsert',
                [
                    'email' => 'test@example.com',
                    'address' => 'bar {{city}}',
                    'status' => 1,
                    'profile_id' => null,
                ],
                true,
                null,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                ],
            ],
            'regular values with update part' => [
                'T_upsert',
                [
                    'email' => 'test@example.com',
                    'address' => 'bar {{city}}',
                    'status' => 1,
                    'profile_id' => null,
                ],
                [
                    'address' => 'foo {{city}}',
                    'status' => 2,
                    'orders' => new Expression('T_upsert.orders + 1'),
                ],
                null,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                    ':qp4' => 'foo {{city}}',
                    ':qp5' => 2,
                ],
            ],
            'regular values without update part' => [
                'T_upsert',
                [
                    'email' => 'test@example.com',
                    'address' => 'bar {{city}}',
                    'status' => 1,
                    'profile_id' => null,
                ],
                false,
                null,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'bar {{city}}',
                    ':qp2' => 1,
                    ':qp3' => null,
                ],
            ],
            'query' => [
                'T_upsert',
                (new Query($db))
                    ->select([
                        'email',
                        'status' => new Expression('2'),
                    ])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                true,
                null,
                [
                    ':qp0' => 'user1',
                ],
            ],
            'query with update part' => [
                'T_upsert',
                (new Query($db))
                    ->select([
                        'email',
                        'status' => new Expression('2'),
                    ])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                [
                    'address' => 'foo {{city}}',
                    'status' => 2,
                    'orders' => new Expression('T_upsert.orders + 1'),
                ],
                null,
                [
                    ':qp0' => 'user1',
                    ':qp1' => 'foo {{city}}',
                    ':qp2' => 2,
                ],
            ],
            'query without update part' => [
                'T_upsert',
                (new Query($db))
                    ->select([
                        'email',
                        'status' => new Expression('2'),
                    ])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                false,
                null,
                [
                    ':qp0' => 'user1',
                ],
            ],
            'values and expressions' => [
                '{{%T_upsert}}',
                [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('now()'),
                ],
                true,
                null,
                [
                    ':qp0' => 'dynamic@example.com',
                ],
            ],
            'values and expressions with update part' => [
                '{{%T_upsert}}',
                [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('now()'),
                ],
                [
                    '[[orders]]' => new Expression('T_upsert.orders + 1'),
                ],
                null,
                [
                    ':qp0' => 'dynamic@example.com',
                ],
            ],
            'values and expressions without update part' => [
                '{{%T_upsert}}',
                [
                    '{{%T_upsert}}.[[email]]' => 'dynamic@example.com',
                    '[[ts]]' => new Expression('now()'),
                ],
                false,
                null,
                [
                    ':qp0' => 'dynamic@example.com',
                ],
            ],
            'query, values and expressions with update part' => [
                '{{%T_upsert}}',
                (new Query($db))
                    ->select([
                        'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                        '[[time]]' => new Expression('now()'),
                    ]),
                [
                    'ts' => 0,
                    '[[orders]]' => new Expression('T_upsert.orders + 1'),
                ],
                null,
                [
                    ':phEmail' => 'dynamic@example.com',
                    ':qp1' => 0,
                ],
            ],
            'query, values and expressions without update part' => [
                '{{%T_upsert}}',
                (new Query($db))
                    ->select([
                        'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                        '[[time]]' => new Expression('now()'),
                    ]),
                [
                    'ts' => 0,
                    '[[orders]]' => new Expression('T_upsert.orders + 1'),
                ],
                null,
                [
                    ':phEmail' => 'dynamic@example.com',
                    ':qp1' => 0,
                ],
            ],
            'no columns to update' => [
                'T_upsert_1',
                [
                    'a' => 1,
                ],
                false,
                null,
                [
                    ':qp0' => 1,
                ],
            ],
        ];
    }

    /**
     * @depends testInitFixtures
     *
     * @dataProvider upsertProvider
     *
     * @param string $table
     * @param ColumnSchema|array $insertColumns
     * @param array|bool|null $updateColumns
     * @param string|string[] $expectedSQL
     * @param array $expectedParams
     *
     * @throws NotSupportedException
     * @throws Exception
     */
    public function testUpsert(string $table, $insertColumns, $updateColumns, $expectedSQL, array $expectedParams): void
    {
        $db = $this->getConnection();

        $actualParams = [];

        $actualSQL = $this->getQueryBuilder(
            true,
            $db->getDriverName() === 'sqlite'
        )->upsert($table, $insertColumns, $updateColumns, $actualParams);

        if (is_string($expectedSQL)) {
            $this->assertSame($expectedSQL, $actualSQL);
        } else {
            $this->assertContains($actualSQL, $expectedSQL);
        }

        if (ArrayHelper::isAssociative($expectedParams)) {
            $this->assertSame($expectedParams, $actualParams);
        } else {
            $this->assertIsOneOf($actualParams, $expectedParams);
        }
    }

    public function batchInsertProviderTrait(): array
    {
        return [
            [
                'customer',
                ['email', 'name', 'address'],
                [['test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine']],
                $this->replaceQuotes(
                    "INSERT INTO [[customer]] ([[email]], [[name]], [[address]])"
                    . " VALUES ('test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine')"
                ),
            ],
            'escape-danger-chars' => [
                'customer',
                ['address'],
                [["SQL-danger chars are escaped: '); --"]],
                'expected' => $this->replaceQuotes(
                    "INSERT INTO [[customer]] ([[address]]) VALUES ('SQL-danger chars are escaped: \'); --')"
                ),
            ],
            [
                'customer',
                ['address'],
                [],
                '',
            ],
            [
                'customer',
                [],
                [['no columns passed']],
                $this->replaceQuotes("INSERT INTO [[customer]] () VALUES ('no columns passed')"),
            ],
            'bool-false, bool2-null' => [
                'type',
                ['bool_col', 'bool_col2'],
                [[false, null]],
                'expected' => $this->replaceQuotes(
                    'INSERT INTO [[type]] ([[bool_col]], [[bool_col2]]) VALUES (0, NULL)'
                ),
            ],
            [
                '{{%type}}',
                ['{{%type}}.[[float_col]]', '[[time]]'],
                [[null, new Expression('now()')]],
                'INSERT INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) VALUES (NULL, now())',
            ],
            'bool-false, time-now()' => [
                '{{%type}}',
                ['{{%type}}.[[bool_col]]', '[[time]]'],
                [[false, new Expression('now()')]],
                'expected' => 'INSERT INTO {{%type}} ({{%type}}.[[bool_col]], [[time]]) VALUES (0, now())',
            ],
        ];
    }

    /**
     * @dataProvider batchInsertProvider
     *
     * @param string $table
     * @param array $columns
     * @param array $value
     * @param string $expected
     */
    public function testBatchInsert(string $table, array $columns, array $value, string $expected): void
    {
        $queryBuilder = $this->getQueryBuilder();

        $sql = $queryBuilder->batchInsert($table, $columns, $value);

        $this->assertEquals($expected, $sql);
    }

    public function updateProvider(): array
    {
        return [
            [
                'customer',
                [
                    'status'     => 1,
                    'updated_at' => new Expression('now()'),
                ],
                [
                    'id' => 100,
                ],
                $this->replaceQuotes(
                    'UPDATE [[customer]] SET [[status]]=:qp0, [[updated_at]]=now() WHERE [[id]]=:qp1'
                ),
                [
                    ':qp0' => 1,
                    ':qp1' => 100,
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateProvider
     *
     * @param string $table
     * @param array $columns
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     */
    public function testUpdate(
        string $table,
        array $columns,
        $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $actualParams = [];

        $actualSQL = $this->getQueryBuilder()->update($table, $columns, $condition, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    public function deleteProvider(): array
    {
        return [
            [
                'user',
                [
                    'is_enabled' => false,
                    'power'      => new Expression('WRONG_POWER()'),
                ],
                $this->replaceQuotes('DELETE FROM [[user]] WHERE ([[is_enabled]]=:qp0) AND ([[power]]=WRONG_POWER())'),
                [
                    ':qp0' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider deleteProvider
     *
     * @param string $table
     * @param array|string $condition
     * @param string $expectedSQL
     * @param array $expectedParams
     */
    public function testDelete(string $table, $condition, string $expectedSQL, array $expectedParams): void
    {
        $actualParams = [];

        $actualSQL = $this->getQueryBuilder()->delete($table, $condition, $actualParams);

        $this->assertSame($expectedSQL, $actualSQL);
        $this->assertSame($expectedParams, $actualParams);
    }

    public function likeConditionProvider(): array
    {
        $conditions = [
            /* simple like */
            [['like', 'name', 'foo%'], '[[name]] LIKE :qp0', [':qp0' => '%foo\%%']],
            [['not like', 'name', 'foo%'], '[[name]] NOT LIKE :qp0', [':qp0' => '%foo\%%']],
            [['or like', 'name', 'foo%'], '[[name]] LIKE :qp0', [':qp0' => '%foo\%%']],
            [['or not like', 'name', 'foo%'], '[[name]] NOT LIKE :qp0', [':qp0' => '%foo\%%']],

            /* like for many values */
            [
                ['like', 'name', ['foo%', '[abc]']],
                '[[name]] LIKE :qp0 AND [[name]] LIKE :qp1',
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%']
            ],
            [
                ['not like', 'name', ['foo%', '[abc]']],
                '[[name]] NOT LIKE :qp0 AND [[name]] NOT LIKE :qp1',
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%']
            ],
            [
                ['or like', 'name', ['foo%', '[abc]']],
                '[[name]] LIKE :qp0 OR [[name]] LIKE :qp1',
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%']
            ],
            [
                ['or not like', 'name', ['foo%', '[abc]']],
                '[[name]] NOT LIKE :qp0 OR [[name]] NOT LIKE :qp1',
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%']
            ],

            /* like with Expression */
            [
                ['like', 'name', new Expression('CONCAT("test", name, "%")')],
                '[[name]] LIKE CONCAT("test", name, "%")',
                []
            ],
            [
                ['not like', 'name', new Expression('CONCAT("test", name, "%")')],
                '[[name]] NOT LIKE CONCAT("test", name, "%")',
                []
            ],
            [
                ['or like', 'name', new Expression('CONCAT("test", name, "%")')],
                '[[name]] LIKE CONCAT("test", name, "%")',
                []
            ],
            [
                ['or not like', 'name', new Expression('CONCAT("test", name, "%")')],
                '[[name]] NOT LIKE CONCAT("test", name, "%")',
                []
            ],
            [
                ['like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] LIKE CONCAT("test", name, "%") AND [[name]] LIKE :qp0',
                [':qp0' => '%\\\ab\_c%']
            ],
            [
                ['not like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] NOT LIKE CONCAT("test", name, "%") AND [[name]] NOT LIKE :qp0',
                [':qp0' => '%\\\ab\_c%']
            ],
            [
                ['or like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] LIKE CONCAT("test", name, "%") OR [[name]] LIKE :qp0',
                [':qp0' => '%\\\ab\_c%']
            ],
            [
                ['or not like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] NOT LIKE CONCAT("test", name, "%") OR [[name]] NOT LIKE :qp0',
                [':qp0' => '%\\\ab\_c%']
            ],

            /**
             * {@see https://github.com/yiisoft/yii2/issues/15630}
             */
            [
                ['like', 'location.title_ru', 'vi%', false],
                '[[location]].[[title_ru]] LIKE :qp0',
                [':qp0' => 'vi%'],
            ],
        ];

        /* adjust dbms specific escaping */
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = $this->replaceQuotes($condition[1]);
            if (!empty($this->likeEscapeCharSql)) {
                preg_match_all('/(?P<condition>LIKE.+?)( AND| OR|$)/', $conditions[$i][1], $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $conditions[$i][1] = str_replace(
                        $match['condition'],
                        $match['condition'] . $this->likeEscapeCharSql,
                        $conditions[$i][1]
                    );
                }
            }

            foreach ($conditions[$i][2] as $name => $value) {
                $conditions[$i][2][$name] = strtr($conditions[$i][2][$name], $this->likeParameterReplacements);
            }
        }

        return $conditions;
    }

    /**
     * @dataProvider likeConditionProvider
     *
     * @param array $condition
     * @param string $expected
     * @param array $expectedParams
     */
    public function testBuildLikeCondition(array $condition, string $expected, array $expectedParams): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->where($condition);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertEquals('SELECT *' . (empty($expected) ? '' : ' WHERE ' . $this->replaceQuotes($expected)), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/15653}
     */
    public function testIssue15653(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))
            ->from('admin_user')
            ->where(['is_deleted' => false]);

        $query
            ->where([])
            ->andWhere(['in', 'id', ['1', '0']]);

        [$sql, $params] = $this->getQueryBuilder()->build($query);

        $this->assertSame($this->replaceQuotes('SELECT * FROM [[admin_user]] WHERE [[id]] IN (:qp0, :qp1)'), $sql);
        $this->assertSame([':qp0' => '1', ':qp1' => '0'], $params);
    }
}
