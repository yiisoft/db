<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestSupport;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaBuilderTrait;

use function array_key_exists;
use function array_values;
use function is_array;
use function str_replace;
use function strncmp;
use function substr;

trait TestQueryBuilderTrait
{
    use SchemaBuilderTrait;

    /**
     * This is not used as a dataprovider for testGetColumnType to speed up the test when used as dataprovider every
     * single line will cause a reconnect with the database which is not needed here.
     */
    public function columnTypes(): array
    {
        $version = $this->getConnection()->getServerVersion();

        $items = [
            [
                Schema::TYPE_BIGINT,
                $this->bigInteger(),
                [
                    'mysql' => 'bigint(20)',
                    'pgsql' => 'bigint',
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
                    'pgsql' => 'bigint NOT NULL',
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
                    'pgsql' => 'bigint CHECK (value > 5)',
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
                    'pgsql' => 'bigint',
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
                    'pgsql' => 'bigint CHECK (value > 5)',
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
                    'pgsql' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            [
                Schema::TYPE_BINARY,
                $this->binary(),
                [
                    'mysql' => 'blob',
                    'pgsql' => 'bytea',
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
                Schema::TYPE_BOOLEAN . ' NOT NULL DEFAULT TRUE',
                $this->boolean()->notNull()->defaultValue(true),
                [
                    'pgsql' => 'boolean NOT NULL DEFAULT TRUE',
                ],
            ],
            [
                Schema::TYPE_BOOLEAN,
                $this->boolean(),
                [
                    'mysql' => 'tinyint(1)',
                    'pgsql' => 'boolean',
                    'sqlite' => 'boolean',
                    'oci' => 'NUMBER(1)',
                    'sqlsrv' => 'bit',
                ],
            ],
            [
                Schema::TYPE_CHAR . ' CHECK (value LIKE \'test%\')',
                $this->char()->check('value LIKE \'test%\''),
                [
                    'pgsql' => 'char(1) CHECK (value LIKE \'test%\')',
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
                    'pgsql' => 'char(1) NOT NULL',
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
                $this->char(6)->unsigned(),
                [
                    'pgsql' => 'char(6)',
                ],
            ],
            [
                Schema::TYPE_CHAR . '(6)',
                $this->char(6),
                [
                    'mysql' => 'char(6)',
                    'pgsql' => 'char(6)',
                    'sqlite' => 'char(6)',
                    'oci' => 'CHAR(6)',
                ],
            ],
            [
                Schema::TYPE_CHAR,
                $this->char(),
                [
                    'mysql' => 'char(1)',
                    'pgsql' => 'char(1)',
                    'sqlite' => 'char(1)',
                    'oci' => 'CHAR(1)',
                ],
            ],
            [
                Schema::TYPE_DATE . ' NOT NULL',
                $this->date()->notNull(),
                [
                    'pgsql' => 'date NOT NULL',
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
                    'pgsql' => 'date',
                    'sqlite' => 'date',
                    'oci' => 'DATE',
                    'sqlsrv' => 'date',
                ],
            ],
            [
                Schema::TYPE_DATETIME . ' NOT NULL',
                $this->dateTime()->notNull(),
                [
                    'mysql' => version_compare($version, '5.6.4', '>=') ? 'datetime(0) NOT NULL' : 'datetime NOT NULL',
                    'pgsql' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'datetime NOT NULL',
                    'oci' => 'TIMESTAMP NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            [
                Schema::TYPE_DATETIME,
                $this->dateTime(),
                [
                    'mysql' => version_compare($version, '5.6.4', '>=') ? 'datetime(0)' : 'datetime',
                    'pgsql' => 'timestamp(0)',
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
                    'pgsql' => 'numeric(10,0) CHECK (value > 5.6)',
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
                    'pgsql' => 'numeric(10,0) NOT NULL',
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
                    'pgsql' => 'numeric(12,4) CHECK (value > 5.6)',
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
                    'pgsql' => 'numeric(12,4)',
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
                    'pgsql' => 'numeric(10,0)',
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
                    'pgsql' => 'double precision CHECK (value > 5.6)',
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
                    'pgsql' => 'double precision NOT NULL',
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
                    'pgsql' => 'double precision CHECK (value > 5.6)',
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
                    'pgsql' => 'double precision',
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
                    'pgsql' => 'double precision CHECK (value > 5.6)',
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
                    'pgsql' => 'double precision NOT NULL',
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
                    'pgsql' => 'double precision CHECK (value > 5.6)',
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
                    'pgsql' => 'double precision',
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
                    'pgsql' => 'integer CHECK (value > 5)',
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
                    'pgsql' => 'integer NOT NULL',
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
                    'pgsql' => 'integer CHECK (value > 5)',
                    'sqlite' => 'integer CHECK (value > 5)',
                    'oci' => 'NUMBER(8) CHECK (value > 5)',
                    'sqlsrv' => 'int CHECK (value > 5)',
                ],
            ],
            [
                Schema::TYPE_INTEGER . '(8)',
                $this->integer(8)->unsigned(),
                [
                    'pgsql' => 'integer',
                ],
            ],
            [
                Schema::TYPE_INTEGER . '(8)',
                $this->integer(8),
                [
                    'mysql' => 'int(8)',
                    'pgsql' => 'integer',
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
                    'pgsql' => 'integer',
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
                    'pgsql' => 'numeric(19,4) CHECK (value > 0.0)',
                    'sqlite' => 'decimal(19,4) CHECK (value > 0.0)',
                    'oci' => 'NUMBER(19,4) CHECK (value > 0.0)',
                    'sqlsrv' => 'decimal(19,4) CHECK (value > 0.0)',
                ],
            ],
            [
                Schema::TYPE_MONEY . ' NOT NULL',
                $this->money()->notNull(),
                [
                    'mysql' => 'decimal(19,4) NOT NULL',
                    'pgsql' => 'numeric(19,4) NOT NULL',
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
                    'pgsql' => 'numeric(16,2) CHECK (value > 0.0)',
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
                    'pgsql' => 'numeric(16,2)',
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
                    'pgsql' => 'numeric(19,4)',
                    'sqlite' => 'decimal(19,4)',
                    'oci' => 'NUMBER(19,4)',
                    'sqlsrv' => 'decimal(19,4)',
                ],
            ],
            [
                Schema::TYPE_PK . ' AFTER `col_before`',
                $this->primaryKey()->after('col_before'),
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY AFTER `col_before`',
                ],
            ],
            [
                Schema::TYPE_PK . ' FIRST',
                $this->primaryKey()->first(),
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
                ],
            ],
            [
                Schema::TYPE_PK . ' FIRST',
                $this->primaryKey()->first()->after('col_before'),
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
                ],
                [
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            [
                Schema::TYPE_PK . '(8) AFTER `col_before`',
                $this->primaryKey(8)->after('col_before'),
                [
                    'mysql' => 'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY AFTER `col_before`',
                ],
            ],
            [
                Schema::TYPE_PK . '(8) FIRST',
                $this->primaryKey(8)->first(),
                [
                    'mysql' => 'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
                ],
            ],
            [
                Schema::TYPE_PK . '(8) FIRST',
                $this->primaryKey(8)->first()->after('col_before'),
                [
                    'mysql' => 'int(8) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
                ],
            ],
            [
                Schema::TYPE_PK . " COMMENT 'test' AFTER `col_before`",
                $this->primaryKey()->comment('test')->after('col_before'),
                [
                    'mysql' => "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'test' AFTER `col_before`",
                ],
            ],
            [
                Schema::TYPE_PK . " COMMENT 'testing \'quote\'' AFTER `col_before`",
                $this->primaryKey()->comment('testing \'quote\'')->after('col_before'),
                [
                    'mysql' => "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'testing \'quote\''"
                        . ' AFTER `col_before`',
                ],
            ],
            [
                Schema::TYPE_PK . ' CHECK (value > 5)',
                $this->primaryKey()->check('value > 5'),
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY CHECK (value > 5)',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY CHECK (value > 5)',
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
                    'pgsql' => 'serial NOT NULL PRIMARY KEY',
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
                    'pgsql' => 'smallint',
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
                    'sqlite' => 'tinyint UNSIGNED',
                ],
            ],
            [
                Schema::TYPE_TINYINT,
                $this->tinyInteger(),
                [
                    'mysql' => 'tinyint(3)',
                    'pgsql' => 'smallint',
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
                    'pgsql' => 'smallint',
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
                    'pgsql' => 'smallint',
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
                    'pgsql' => 'varchar(255) CHECK (value LIKE \'test%\')',
                    'oci' => 'VARCHAR2(255) CHECK (value LIKE \'test%\')',
                ],
            ],
            [
                Schema::TYPE_STRING . ' NOT NULL',
                $this->string()->notNull(),
                [
                    'mysql' => 'varchar(255) NOT NULL',
                    'pgsql' => 'varchar(255) NOT NULL',
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
                    'pgsql' => 'varchar(32) CHECK (value LIKE \'test%\')',
                    'oci' => 'VARCHAR2(32) CHECK (value LIKE \'test%\')',
                ],
            ],
            [
                Schema::TYPE_STRING . '(32)',
                $this->string(32),
                [
                    'mysql' => 'varchar(32)',
                    'pgsql' => 'varchar(32)',
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
                    'pgsql' => 'varchar(255)',
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
                    'pgsql' => 'text CHECK (value LIKE \'test%\')',
                    'oci' => 'CLOB CHECK (value LIKE \'test%\')',
                ],
            ],
            [
                Schema::TYPE_TEXT . ' NOT NULL',
                $this->text()->notNull(),
                [
                    'mysql' => 'text NOT NULL',
                    'pgsql' => 'text NOT NULL',
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
                    'pgsql' => 'text CHECK (value LIKE \'test%\')',
                    'oci' => 'CLOB CHECK (value LIKE \'test%\')',
                ],
                Schema::TYPE_TEXT . ' CHECK (value LIKE \'test%\')',
            ],
            [
                Schema::TYPE_TEXT . ' NOT NULL',
                $this->text()->notNull(),
                [
                    'mysql' => 'text NOT NULL',
                    'pgsql' => 'text NOT NULL',
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
                    'pgsql' => 'text',
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
                    'pgsql' => 'text',
                    'sqlite' => 'text',
                    'oci' => 'CLOB',
                    'sqlsrv' => 'nvarchar(max)',
                ],
            ],
            [
                Schema::TYPE_TIME . ' NOT NULL',
                $this->time()->notNull(),
                [
                    'mysql' => version_compare($version, '5.6.4', '>=') ? 'time(0) NOT NULL' : 'time NOT NULL',
                    'pgsql' => 'time(0) NOT NULL',
                    'sqlite' => 'time NOT NULL',
                    'oci' => 'TIMESTAMP NOT NULL',
                    'sqlsrv' => 'time NOT NULL',
                ],
            ],
            [
                Schema::TYPE_TIME,
                $this->time(),
                [
                    'mysql' => version_compare($version, '5.6.4', '>=') ? 'time(0)' : 'time',
                    'pgsql' => 'time(0)',
                    'sqlite' => 'time',
                    'oci' => 'TIMESTAMP',
                    'sqlsrv' => 'time',
                ],
            ],
            [
                Schema::TYPE_TIMESTAMP . ' NOT NULL',
                $this->timestamp()->notNull(),
                [
                    'mysql' => version_compare($version, '5.6.4', '>=') ? 'timestamp(0) NOT NULL'
                        : 'timestamp NOT NULL',
                    'pgsql' => 'timestamp(0) NOT NULL',
                    'sqlite' => 'timestamp NOT NULL',
                    'oci' => 'TIMESTAMP NOT NULL',
                    'sqlsrv' => 'datetime NOT NULL',
                ],
            ],
            [
                Schema::TYPE_TIMESTAMP . ' NULL DEFAULT NULL',
                $this->timestamp()->defaultValue(null),
                [
                    'mysql' => version_compare($version, '5.6.4', '>=') ? 'timestamp(0) NULL DEFAULT NULL'
                        : 'timestamp NULL DEFAULT NULL',
                    'pgsql' => 'timestamp(0) NULL DEFAULT NULL',
                    'sqlite' => 'timestamp NULL DEFAULT NULL',
                    'sqlsrv' => 'datetime NULL DEFAULT NULL',
                ],
            ],
            [
                Schema::TYPE_TIMESTAMP . '(4)',
                $this->timestamp(4),
                [
                    'pgsql' => 'timestamp(4)',
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
                    'pgsql' => 'timestamp(0)',
                    'sqlite' => 'timestamp',
                    'oci' => 'TIMESTAMP',
                    'sqlsrv' => 'datetime',
                ],
            ],
            [
                Schema::TYPE_UPK,
                $this->primaryKey()->unsigned(),
                [
                    'mysql' => 'int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'serial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            [
                Schema::TYPE_UBIGPK,
                $this->bigPrimaryKey()->unsigned(),
                [
                    'mysql' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                    'pgsql' => 'bigserial NOT NULL PRIMARY KEY',
                    'sqlite' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
                ],
            ],
            [
                Schema::TYPE_INTEGER . " COMMENT 'test comment'",
                $this->integer()->comment('test comment'),
                [
                    'mysql' => "int(11) COMMENT 'test comment'",
                    'sqlsrv' => 'int',
                ],
                [
                    'sqlsrv' => 'integer',
                ],
            ],
            [
                Schema::TYPE_PK . " COMMENT 'test comment'",
                $this->primaryKey()->comment('test comment'),
                [
                    'mysql' => "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'test comment'",
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
                [
                    'sqlsrv' => 'pk',
                ],
            ],
            [
                Schema::TYPE_PK . ' FIRST',
                $this->primaryKey()->first(),
                [
                    'mysql' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST',
                    'sqlsrv' => 'int IDENTITY PRIMARY KEY',
                ],
                [
                    'oci' => 'NUMBER(10) NOT NULL PRIMARY KEY FIRST',
                    'sqlsrv' => 'pk',
                ],
            ],
            [
                Schema::TYPE_INTEGER . ' FIRST',
                $this->integer()->first(),
                [
                    'mysql' => 'int(11) FIRST',
                    'sqlsrv' => 'int',
                ],
                [
                    'oci' => 'NUMBER(10) FIRST',
                    'pgsql' => 'integer',
                    'sqlsrv' => 'integer',
                ],
            ],
            [
                Schema::TYPE_STRING . ' FIRST',
                $this->string()->first(),
                [
                    'mysql' => 'varchar(255) FIRST',
                    'sqlsrv' => 'nvarchar(255)',
                ],
                [
                    'oci' => 'VARCHAR2(255) FIRST',
                    'sqlsrv' => 'string',
                ],
            ],
            [
                Schema::TYPE_INTEGER . ' NOT NULL FIRST',
                $this->integer()->append('NOT NULL')->first(),
                [
                    'mysql' => 'int(11) NOT NULL FIRST',
                    'sqlsrv' => 'int NOT NULL',
                ],
                [
                    'oci' => 'NUMBER(10) NOT NULL FIRST',
                    'sqlsrv' => 'integer NOT NULL',
                ],
            ],
            [
                Schema::TYPE_STRING . ' NOT NULL FIRST',
                $this->string()->append('NOT NULL')->first(),
                [
                    'mysql' => 'varchar(255) NOT NULL FIRST',
                    'sqlsrv' => 'nvarchar(255) NOT NULL',
                ],
                [
                    'oci' => 'VARCHAR2(255) NOT NULL FIRST',
                    'sqlsrv' => 'string NOT NULL',
                ],
            ],
            [
                Schema::TYPE_JSON,
                $this->json(),
                [
                    'pgsql' => 'jsonb',
                ],
            ],
        ];

        $driverName = $this->getConnection()->getDriver()->getDriverName();
        foreach ($items as $i => $item) {
            if (array_key_exists($driverName, $item[2])) {
                $item[2] = $item[2][$driverName];
                $items[$i] = $item;
            } else {
                unset($items[$i]);
            }
        }

        return array_values($items);
    }

    public function testGetColumnType(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        foreach ($this->columnTypes() as $item) {
            [$column, $builder, $expected] = $item;

            $driverName = $db->getDriver()->getDriverName();
            if (isset($item[3][$driverName])) {
                $expectedColumnSchemaBuilder = $item[3][$driverName];
            } elseif (isset($item[3]) && !is_array($item[3])) {
                $expectedColumnSchemaBuilder = $item[3];
            } else {
                $expectedColumnSchemaBuilder = $column;
            }

            $this->assertEquals($expectedColumnSchemaBuilder, $builder->__toString());
            $this->assertEquals($expected, $qb->getColumnType($column));
            $this->assertEquals($expected, $qb->getColumnType($builder));
        }
    }

    public function testCreateTableColumnTypes(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        if ($db->getTableSchema('column_type_table', true) !== null) {
            $db->createCommand($qb->dropTable('column_type_table'))->execute();
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

        $db->createCommand($qb->createTable('column_type_table', $columns))->execute();
        $this->assertNotEmpty($db->getTableSchema('column_type_table', true));
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

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

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

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

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

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

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

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

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

        [$actualQuerySql, $queryParams] = $db->getQueryBuilder()->build($query);

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

        [$sql, $params] = $db->getQueryBuilder()->build($query);

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

        $expressionString = $this->replaceQuotes(
            "case t.Status_Id when 1 then 'Acknowledge' when 2 then 'No Action' else 'Unknown Action'"
            . ' END as [[Next Action]]'
        );
        $this->assertIsString($expressionString);

        $query = (new Query($db))
            ->select([
                'ID' => 't.id',
                'gsm.username as GSM',
                'part.Part',
                'Part Cost' => 't.Part_Cost',
                'st_x(location::geometry) as lon',
                new Expression($expressionString),
            ])
            ->from('tablename');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

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

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT 1 AS ab FROM [[tablename]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('1 AS ab'))
            ->addSelect(new Expression('2 AS cd'))
            ->addSelect(['ef' => new Expression('3')])
            ->from('tablename');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT 1 AS ab, 2 AS cd, 3 AS [[ef]] FROM [[tablename]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->select(new Expression('SUBSTR(name, 0, :len)', [':len' => 4]))
            ->from('tablename');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

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

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM {{%user}} USE INDEX (primary)');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        $query = (new Query($db))
            ->from([new Expression('{{user}} {{t}} FORCE INDEX (primary) IGNORE INDEX FOR ORDER BY (i1)')])
            ->leftJoin(['p' => 'profile'], 'user.id = profile.user_id USE INDEX (i2)');

        [$sql, $params] = $db->getQueryBuilder()->build($query);

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
        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = accounts.id) [[activeusers]]'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* query subquery with params */
        $subquery = (new Query($db))->from('user')->where('account_id = :id', ['id' => 1]);

        $query = (new Query($db))->from(['activeusers' => $subquery])->where('abc = :abc', ['abc' => 'abc']);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes(
            'SELECT * FROM (SELECT * FROM [[user]] WHERE account_id = :id) [[activeusers]] WHERE abc = :abc'
        );

        $this->assertEquals($expected, $sql);
        $this->assertEquals(['id' => 1, 'abc' => 'abc'], $params);

        /* simple subquery */
        $subquery = '(SELECT * FROM user WHERE account_id = accounts.id)';

        $query = (new Query($db))->from(['activeusers' => $subquery]);

        /* SELECT * FROM (SELECT * FROM [[user]] WHERE [[active]] = 1) [[activeusers]]; */
        [$sql, $params] = $db->getQueryBuilder()->build($query);

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

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->orderBy(['name' => SORT_ASC, 'date' => SORT_DESC]);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] ORDER BY [[name]], [[date]] DESC');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->orderBy(new Expression('SUBSTR(name, 3, 4) DESC, x ASC'));

        [$sql, $params] = $db->getQueryBuilder()->build($query);

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

        [$sql, $params] = $db->getQueryBuilder()->build($query);

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

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* array syntax */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->groupBy(['name', 'date']);

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] GROUP BY [[name]], [[date]]');

        $this->assertEquals($expected, $sql);
        $this->assertEmpty($params);

        /* expression */
        $query = (new Query($db))
            ->select('*')
            ->from('operations')
            ->where('account_id = accounts.id')
            ->groupBy(new Expression('SUBSTR(name, 0, 1), x'));

        [$sql, $params] = $db->getQueryBuilder()->build($query);

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

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $expected = $this->replaceQuotes('SELECT * FROM [[operations]] GROUP BY SUBSTR(name, 0, :to), x');

        $this->assertEquals($expected, $sql);
        $this->assertEquals([':to' => 4], $params);
    }

    /**
     * Dummy test to speed up QB's tests which rely on DB schema.
     */
    public function testInitFixtures(): void
    {
        $db = $this->getConnection();
        $this->assertInstanceOf(QueryBuilder::class, $db->getQueryBuilder());
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

        [$sql, $params] = $db->getQueryBuilder()->build($query);

        $this->assertSame($this->replaceQuotes('SELECT * FROM [[admin_user]] WHERE [[id]] IN (:qp0, :qp1)'), $sql);
        $this->assertSame([':qp0' => '1', ':qp1' => '0'], $params);
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
}
