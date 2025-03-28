<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Stringable;
use Yiisoft\Db\Tests\Support\TestTrait;

class CommandProvider
{
    use TestTrait;

    protected static string $driverName = 'db';

    public static function addForeignKey(): array
    {
        return [
            ['{{test_fk_constraint_1}}', '{{test_fk}}', 'int1', 'int3', 'test_fk_constraint_1'],
            ['{{test_fk_constraint_2}}', '{{test_fk}}', ['int1'], 'int3', 'test_fk_constraint_2'],
            ['{{test_fk_constraint_3}}', '{{test_fk}}', ['int1'], ['int3'], 'test_fk_constraint_3'],
            ['{{test_fk_constraint_4}}', '{{test_fk}}', ['int1', 'int2'], ['int3', 'int4'], 'test_fk_constraint_4'],
        ];
    }

    public static function addForeignKeySql(): array
    {
        return [
            [
                'int1',
                'int3',
                null,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['int1'],
                'int3',
                null,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['int1'],
                ['int3'],
                null,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['int1'],
                ['int3'],
                ReferentialAction::CASCADE,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]]) ON DELETE CASCADE
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['int1'],
                ['int3'],
                ReferentialAction::CASCADE,
                ReferentialAction::CASCADE,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]]) ON DELETE CASCADE ON UPDATE CASCADE
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['int1', 'int2'],
                ['int3', 'int4'],
                null,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]], [[int2]]) REFERENCES [[fk_referenced_table]] ([[int3]], [[int4]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['int1', 'int2'],
                ['int3', 'int4'],
                ReferentialAction::CASCADE,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]], [[int2]]) REFERENCES [[fk_referenced_table]] ([[int3]], [[int4]]) ON DELETE CASCADE
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['int1', 'int2'],
                ['int3', 'int4'],
                ReferentialAction::CASCADE,
                ReferentialAction::CASCADE,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]], [[int2]]) REFERENCES [[fk_referenced_table]] ([[int3]], [[int4]]) ON DELETE CASCADE ON UPDATE CASCADE
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['int1'],
                ['int3'],
                ReferentialAction::NO_ACTION,
                ReferentialAction::RESTRICT,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]]) ON DELETE NO ACTION ON UPDATE RESTRICT
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['int1'],
                ['int3'],
                ReferentialAction::SET_DEFAULT,
                ReferentialAction::SET_NULL,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]]) ON DELETE SET DEFAULT ON UPDATE SET NULL
                    SQL,
                    static::$driverName,
                ),
            ],
        ];
    }

    public static function addPrimaryKey(): array
    {
        return [
            ['{{test_pk_constraint_1}}', '{{test_pk}}', 'int1'],
            ['{{test_pk_constraint_2}}', '{{test_pk}}', ['int1']],
            ['{{test_pk_constraint_3}}', 'test_pk', ['int1', 'int2']],
        ];
    }

    public static function addPrimaryKeySql(): array
    {
        return [
            [
                '{{test_fk_constraint_1}}',
                '{{test_fk}}',
                'int1',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_1]] PRIMARY KEY ([[int1]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{test_fk_constraint_2}}',
                '{{test_fk}}',
                ['int1'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_2]] PRIMARY KEY ([[int1]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{test_fk_constraint_3}}',
                '{{test_fk}}',
                ['int3', 'int4'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_3]] PRIMARY KEY ([[int3]], [[int4]])
                    SQL,
                    static::$driverName,
                ),
            ],
        ];
    }

    public static function addUnique(): array
    {
        return [
            ['{{test_unique_constraint_1}}', '{{test_unique}}', 'int1'],
            ['{{test_unique_constraint_2}}', '{{test_unique}}', ['int1']],
            ['{{test_unique_constraint_3}}', '{{test_unique}}', ['int1', 'int2']],
        ];
    }

    public static function addUniqueSql(): array
    {
        return [
            [
                '{{test_fk_constraint_1}}',
                '{{test_fk}}',
                'int1',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_1]] UNIQUE ([[int1]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{test_fk_constraint_2}}',
                '{{test_fk}}',
                ['int1'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_2]] UNIQUE ([[int1]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{test_fk_constraint_3}}',
                '{{test_fk}}',
                ['int3', 'int4'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_3]] UNIQUE ([[int3]], [[int4]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{test_fk_constraint_3}}',
                '{{test_fk}}',
                ['int1', 'int2'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_3]] UNIQUE ([[int1]], [[int2]])
                    SQL,
                    static::$driverName,
                ),
            ],
        ];
    }

    public static function batchInsert(): array
    {
        return [
            'multirow' => [
                'type',
                'values' => [
                    ['0', '0.0', 'test string', true],
                    [false, 0, 'test string2', false],
                ],
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3), (:qp4, :qp5, :qp6, :qp7)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 0,
                    ':qp1' => 0.0,
                    ':qp2' => 'test string',
                    ':qp3' => true,
                    ':qp4' => 0,
                    ':qp5' => 0.0,
                    ':qp6' => 'test string2',
                    ':qp7' => false,
                ],
                2,
            ],
            'issue11242' => [
                'type',
                'values' => [[1.0, 1.1, 'Kyiv {{city}}, Ukraine', true]],
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                /**
                 * {@see https://github.com/yiisoft/yii2/issues/11242}
                 *
                 * Make sure curly bracelets (`{{..}}`) in values will not be escaped
                 */
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 1.1,
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => true,
                ],
            ],
            'table name with column name with brackets' => [
                '{{%type}}',
                'values' => [['0', '0.0', 'Kyiv {{city}}, Ukraine', false]],
                ['{{%type}}.[[int_col]]', '[[float_col]]', 'char_col', 'bool_col'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 0,
                    ':qp1' => 0.0,
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                ],
            ],
            'binds params from expression' => [
                '{{%type}}',
                /**
                 * This example is completely useless. This feature of batchInsert is intended to be used with complex
                 * expression objects, such as JsonExpression.
                 */
                'values' => [[new Expression(':exp1', [':exp1' => 42]), 1, 'test', false]],
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:exp1, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':exp1' => 42,
                    ':qp1' => 1.0,
                    ':qp2' => 'test',
                    ':qp3' => false,
                ],
            ],
            'with associative values with different keys' => [
                'type',
                'values' => [['int' => '1.0', 'float' => '2', 'char' => 10, 'bool' => 1]],
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 2.0,
                    ':qp2' => '10',
                    ':qp3' => true,
                ],
            ],
            'with associative values with different keys and columns with keys' => [
                'type',
                'values' => [['int' => '1.0', 'float' => '2', 'char' => 10, 'bool' => 1]],
                ['a' => 'int_col', 'b' => 'float_col', 'c' => 'char_col', 'd' => 'bool_col'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 2.0,
                    ':qp2' => '10',
                    ':qp3' => true,
                ],
            ],
            'with associative values with keys of column names' => [
                'type',
                'values' => [['bool_col' => 1, 'char_col' => 10, 'int_col' => '1.0', 'float_col' => '2']],
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp2, :qp3, :qp1, :qp0)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => true,
                    ':qp1' => '10',
                    ':qp2' => 1,
                    ':qp3' => 2.0,
                ],
            ],
            'with associative values with keys of column keys' => [
                'type',
                'values' => [['bool' => 1, 'char' => 10, 'int' => '1.0', 'float' => '2']],
                ['int' => 'int_col', 'float' => 'float_col', 'char' => 'char_col', 'bool' => 'bool_col'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp2, :qp3, :qp1, :qp0)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => true,
                    ':qp1' => '10',
                    ':qp2' => 1,
                    ':qp3' => 2.0,
                ],
            ],
            'with shuffled indexes of values' => [
                'type',
                'values' => [[3 => 1, 2 => 10, 0 => '1.0', 1 => '2']],
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp2, :qp3, :qp1, :qp0)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => true,
                    ':qp1' => '10',
                    ':qp2' => 1,
                    ':qp3' => 2.0,
                ],
            ],
            'empty columns and associative values' => [
                'type',
                'values' => [['int_col' => '1.0', 'float_col' => '2', 'char_col' => 10, 'bool_col' => 1]],
                [],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 2.0,
                    ':qp2' => '10',
                    ':qp3' => true,
                ],
            ],
            'empty columns and objects' => [
                'type',
                'values' => [(object)['int_col' => '1.0', 'float_col' => '2', 'char_col' => 10, 'bool_col' => 1]],
                [],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 2.0,
                    ':qp2' => '10',
                    ':qp3' => true,
                ],
            ],
            'empty columns and a Traversable value' => [
                'type',
                'values' => [new ArrayIterator(['int_col' => '1.0', 'float_col' => '2', 'char_col' => 10, 'bool_col' => 1])],
                [],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 2.0,
                    ':qp2' => '10',
                    ':qp3' => true,
                ],
            ],
            'empty columns and Traversable values' => [
                'type',
                'values' => new class () implements IteratorAggregate {
                    public function getIterator(): Traversable
                    {
                        yield ['int_col' => '1.0', 'float_col' => '2', 'char_col' => 10, 'bool_col' => 1];
                    }
                },
                [],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 2.0,
                    ':qp2' => '10',
                    ':qp3' => true,
                ],
            ],
            'binds json params' => [
                '{{%type}}',
                [
                    [1, 'a', 0.0, true, ['a' => 1, 'b' => true, 'c' => [1, 2, 3]]],
                    [2, 'b', -1.0, false, new JsonExpression(['d' => 'e', 'f' => false, 'g' => [4, 5, null]])],
                ],
                ['int_col', 'char_col', 'float_col', 'bool_col', 'json_col'],
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[type]] ([[int_col]], [[char_col]], [[float_col]], [[bool_col]], [[json_col]])'
                        . ' VALUES (:qp0, :qp1, :qp2, :qp3, :qp4), (:qp5, :qp6, :qp7, :qp8, :qp9)',
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 'a',
                    ':qp2' => 0.0,
                    ':qp3' => true,
                    ':qp4' => '{"a":1,"b":true,"c":[1,2,3]}',

                    ':qp5' => 2,
                    ':qp6' => 'b',
                    ':qp7' => -1.0,
                    ':qp8' => false,
                    ':qp9' => '{"d":"e","f":false,"g":[4,5,null]}',
                ],
                2,
            ],
        ];
    }

    public static function createIndex(): array
    {
        return [
            [['col1' => ColumnBuilder::integer()], ['col1'], null, null],
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::UNIQUE, null],
            [['col1' => ColumnBuilder::integer(), 'col2' => ColumnBuilder::integer()], ['col1', 'col2'], null, null],
        ];
    }

    public static function createIndexSql(): array
    {
        return [
            [
                '{{name}}',
                '{{table}}',
                'column',
                '',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE INDEX [[name]] ON [[table]] ([[column]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                '',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                IndexType::UNIQUE,
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE UNIQUE INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                'FULLTEXT',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE FULLTEXT INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                'SPATIAL',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE SPATIAL INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                'BITMAP',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE BITMAP INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    static::$driverName,
                ),
            ],
        ];
    }

    public static function invalidSelectColumns(): array
    {
        return [[[]], ['*'], [['*']]];
    }

    public static function rawSql(): array
    {
        return [
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = :id
                SQL,
                [':id' => 1],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = :id
                SQL,
                ['id' => 1],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = :id
                SQL,
                ['id' => new Param('1 OR 1=1', DataType::INTEGER)],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = :id
                SQL,
                ['id' => null],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = NULL
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = :base OR [[id]] = :basePrefix
                SQL,
                ['base' => 1, 'basePrefix' => 2],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1 OR [[id]] = 2
                    SQL,
                    static::$driverName,
                ),
            ],
            /**
             * {@see https://github.com/yiisoft/yii2/issues/9268}
             */
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[active]] = :active
                SQL,
                [':active' => false],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[active]] = FALSE
                    SQL,
                    static::$driverName,
                ),
            ],
            /**
             * {@see https://github.com/yiisoft/yii2/issues/15122}
             */
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] IN (:ids)
                SQL,
                [':ids' => new Expression(implode(', ', [1, 2]))],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] IN (1, 2)
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                'SELECT * FROM customer WHERE id  = ? AND active = ?',
                [1, false],
                <<<SQL
                SELECT * FROM customer WHERE id  = 1 AND active = FALSE
                SQL,
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = ?
                SQL,
                [1],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = ? OR [[id]] = ?
                SQL,
                [1, 2],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1 OR [[id]] = 2
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[name]] = :name
                SQL,
                ['name' => new Stringable('Alfa')],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[name]] = 'Alfa'
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[product]] WHERE [[price]] = :price
                SQL,
                ['price' => 123.45],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[product]] WHERE [[price]] = 123.45
                    SQL,
                    static::$driverName,
                ),
            ],
        ];
    }

    public static function update(): array
    {
        return [
            [
                '{{customer}}',
                ['name' => '{{test}}'],
                [],
                [],
                ['name' => '{{test}}'],
                3,
            ],
            [
                '{{customer}}',
                ['name' => '{{test}}'],
                ['id' => 1],
                [],
                ['name' => '{{test}}'],
                1,
            ],
            [
                '{{customer}}',
                ['{{customer}}.name' => '{{test}}'],
                ['id' => 1],
                [],
                ['name' => '{{test}}'],
                1,
            ],
            [
                'customer',
                ['status' => new Expression('1 + 2')],
                ['id' => 2],
                [],
                ['status' => 3],
                1,
            ],
            [
                '{{customer}}',
                ['status' => new Expression(
                    '1 + :val',
                    ['val' => new Expression('2 + :val', ['val' => 3])]
                )],
                '[[name]] != :val',
                ['val' => new Expression('LOWER(:val)', ['val' => 'USER1'])],
                ['name' => 'user2', 'status' => 6],
                2,
            ],
        ];
    }

    public static function upsert(): array
    {
        return [
            'regular values' => [
                ['params' => ['T_upsert', ['email' => 'foo@example.com', 'address' => 'Earth', 'status' => 3]]],
                ['params' => ['T_upsert', ['email' => 'foo@example.com', 'address' => 'Universe', 'status' => 1]]],
            ],
            'regular values with update part' => [
                ['params' => [
                    'T_upsert',
                    ['email' => 'foo@example.com', 'address' => 'Earth', 'status' => 3],
                    ['address' => 'Moon', 'status' => 2],
                ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        ['email' => 'foo@example.com', 'address' => 'Universe', 'status' => 1],
                        ['address' => 'Moon', 'status' => 2],
                    ],
                    'expected' => ['email' => 'foo@example.com', 'address' => 'Moon', 'status' => 2],
                ],
            ],
            'regular values without update part' => [
                ['params' => ['T_upsert', ['email' => 'foo@example.com', 'address' => 'Earth', 'status' => 3], false]],
                [
                    'params' => [
                        'T_upsert',
                        ['email' => 'foo@example.com', 'address' => 'Universe', 'status' => 1],
                        false,
                    ],
                    'expected' => ['email' => 'foo@example.com', 'address' => 'Earth', 'status' => 3],
                ],
            ],
            'query' => [
                [
                    'params' => [
                        'T_upsert',
                        (new Query(static::getDb()))
                            ->select(['email', 'address', 'status' => new Expression('1')])
                            ->from('{{customer}}')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'address1', 'status' => 1],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new Query(static::getDb()))
                            ->select(['email', 'address', 'status' => new Expression('2')])
                            ->from('{{customer}}')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'address1', 'status' => 2],
                ],
            ],
            'query with update part' => [
                [
                    'params' => [
                        'T_upsert',
                        (new Query(static::getDb()))
                            ->select(['email', 'address', 'status' => new Expression('1')])
                            ->from('{{customer}}')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        ['address' => 'Moon', 'status' => 2],
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'address1', 'status' => 1],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new Query(static::getDb()))
                            ->select(['email', 'address', 'status' => new Expression('3')])
                            ->from('{{customer}}')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        ['address' => 'Moon', 'status' => 2],
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'Moon', 'status' => 2],
                ],
            ],
            'query without update part' => [
                [
                    'params' => [
                        'T_upsert',
                        (new Query(static::getDb()))
                            ->select(['email', 'address', 'status' => new Expression('1')])
                            ->from('{{customer}}')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        false,
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'address1', 'status' => 1],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new Query(static::getDb()))
                            ->select(['email', 'address', 'status' => new Expression('2')])
                            ->from('{{customer}}')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        false,
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'address1', 'status' => 1],
                ],
            ],
        ];
    }

    public static function columnTypes(): array
    {
        return [
            [ColumnType::INTEGER],
            [ColumnBuilder::string(100)],
        ];
    }

    public static function dropTable(): iterable
    {
        yield ['DROP TABLE [[table]]', null, null];
        yield ['DROP TABLE IF EXISTS [[table]]', true, null];
        yield ['DROP TABLE [[table]]', false, null];
        yield ['DROP TABLE [[table]] CASCADE', null, true];
        yield ['DROP TABLE [[table]]', null, false];
        yield ['DROP TABLE [[table]]', false, false];
        yield ['DROP TABLE IF EXISTS [[table]] CASCADE', true, true];
        yield ['DROP TABLE IF EXISTS [[table]]', true, false];
        yield ['DROP TABLE [[table]] CASCADE', false, true];
    }
}
