<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use ArrayIterator;
use IteratorAggregate;
use Traversable;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Tests\Support\Stringable;
use Yiisoft\Db\Tests\Support\TestTrait;

use function str_repeat;

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
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]])
                    SQL
                ),
            ],
            [
                ['int1'],
                'int3',
                null,
                null,
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]])
                    SQL
                ),
            ],
            [
                ['int1'],
                ['int3'],
                null,
                null,
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]])
                    SQL
                ),
            ],
            [
                ['int1'],
                ['int3'],
                ReferentialAction::CASCADE,
                null,
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]]) ON DELETE CASCADE
                    SQL
                ),
            ],
            [
                ['int1'],
                ['int3'],
                ReferentialAction::CASCADE,
                ReferentialAction::CASCADE,
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]]) ON DELETE CASCADE ON UPDATE CASCADE
                    SQL
                ),
            ],
            [
                ['int1', 'int2'],
                ['int3', 'int4'],
                null,
                null,
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]], [[int2]]) REFERENCES [[fk_referenced_table]] ([[int3]], [[int4]])
                    SQL
                ),
            ],
            [
                ['int1', 'int2'],
                ['int3', 'int4'],
                ReferentialAction::CASCADE,
                null,
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]], [[int2]]) REFERENCES [[fk_referenced_table]] ([[int3]], [[int4]]) ON DELETE CASCADE
                    SQL
                ),
            ],
            [
                ['int1', 'int2'],
                ['int3', 'int4'],
                ReferentialAction::CASCADE,
                ReferentialAction::CASCADE,
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]], [[int2]]) REFERENCES [[fk_referenced_table]] ([[int3]], [[int4]]) ON DELETE CASCADE ON UPDATE CASCADE
                    SQL
                ),
            ],
            [
                ['int1'],
                ['int3'],
                ReferentialAction::NO_ACTION,
                ReferentialAction::RESTRICT,
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]]) ON DELETE NO ACTION ON UPDATE RESTRICT
                    SQL
                ),
            ],
            [
                ['int1'],
                ['int3'],
                ReferentialAction::SET_DEFAULT,
                ReferentialAction::SET_NULL,
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[fk_table]] ADD CONSTRAINT [[fk_constraint]] FOREIGN KEY ([[int1]]) REFERENCES [[fk_referenced_table]] ([[int3]]) ON DELETE SET DEFAULT ON UPDATE SET NULL
                    SQL
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
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_1]] PRIMARY KEY ([[int1]])
                    SQL
                ),
            ],
            [
                '{{test_fk_constraint_2}}',
                '{{test_fk}}',
                ['int1'],
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_2]] PRIMARY KEY ([[int1]])
                    SQL
                ),
            ],
            [
                '{{test_fk_constraint_3}}',
                '{{test_fk}}',
                ['int3', 'int4'],
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_3]] PRIMARY KEY ([[int3]], [[int4]])
                    SQL
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
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_1]] UNIQUE ([[int1]])
                    SQL
                ),
            ],
            [
                '{{test_fk_constraint_2}}',
                '{{test_fk}}',
                ['int1'],
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_2]] UNIQUE ([[int1]])
                    SQL
                ),
            ],
            [
                '{{test_fk_constraint_3}}',
                '{{test_fk}}',
                ['int3', 'int4'],
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_3]] UNIQUE ([[int3]], [[int4]])
                    SQL
                ),
            ],
            [
                '{{test_fk_constraint_3}}',
                '{{test_fk}}',
                ['int1', 'int2'],
                static::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[test_fk]] ADD CONSTRAINT [[test_fk_constraint_3]] UNIQUE ([[int1]], [[int2]])
                    SQL
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
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (0, 0, :qp0, TRUE), (0, 0, :qp1, FALSE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => 'test string',
                    ':qp1' => 'test string2',
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
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 1.1, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => 'Kyiv {{city}}, Ukraine',
                ],
            ],
            'table name with column name with brackets' => [
                '{{%type}}',
                'values' => [['0', '0.0', 'Kyiv {{city}}, Ukraine', false]],
                ['{{%type}}.[[int_col]]', '[[float_col]]', 'char_col', 'bool_col'],
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (0, 0, :qp0, FALSE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => 'Kyiv {{city}}, Ukraine',
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
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:exp1, 1, :qp1, FALSE)
                    SQL
                ),
                'expectedParams' => [
                    ':exp1' => 42,
                    ':qp1' => 'test',
                ],
            ],
            'with associative values with different keys' => [
                'type',
                'values' => [['int' => '1.0', 'float' => '2', 'char' => 10, 'bool' => 1]],
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 2, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => '10',
                ],
            ],
            'with associative values with different keys and columns with keys' => [
                'type',
                'values' => [['int' => '1.0', 'float' => '2', 'char' => 10, 'bool' => 1]],
                ['a' => 'int_col', 'b' => 'float_col', 'c' => 'char_col', 'd' => 'bool_col'],
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 2, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => '10',
                ],
            ],
            'with associative values with keys of column names' => [
                'type',
                'values' => [['bool_col' => 1, 'char_col' => 10, 'int_col' => '1.0', 'float_col' => '2']],
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 2, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => '10',
                ],
            ],
            'with associative values with keys of column keys' => [
                'type',
                'values' => [['bool' => 1, 'char' => 10, 'int' => '1.0', 'float' => '2']],
                ['int' => 'int_col', 'float' => 'float_col', 'char' => 'char_col', 'bool' => 'bool_col'],
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 2, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => '10',
                ],
            ],
            'with shuffled indexes of values' => [
                'type',
                'values' => [[3 => 1, 2 => 10, 0 => '1.0', 1 => '2']],
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 2, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => '10',
                ],
            ],
            'empty columns and associative values' => [
                'type',
                'values' => [['int_col' => '1.0', 'float_col' => '2', 'char_col' => 10, 'bool_col' => 1]],
                [],
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 2, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => '10',
                ],
            ],
            'empty columns and objects' => [
                'type',
                'values' => [(object)['int_col' => '1.0', 'float_col' => '2', 'char_col' => 10, 'bool_col' => 1]],
                [],
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 2, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => '10',
                ],
            ],
            'empty columns and a Traversable value' => [
                'type',
                'values' => [new ArrayIterator(['int_col' => '1.0', 'float_col' => '2', 'char_col' => 10, 'bool_col' => 1])],
                [],
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 2, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => '10',
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
                'expected' => static::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (1, 2, :qp0, TRUE)
                    SQL
                ),
                'expectedParams' => [
                    ':qp0' => '10',
                ],
            ],
            'binds json params' => [
                '{{%type}}',
                [
                    [1, 'a', 0.0, true, ['a' => 1, 'b' => true, 'c' => [1, 2, 3]]],
                    [2, 'b', -1.0, false, new JsonExpression(['d' => 'e', 'f' => false, 'g' => [4, 5, null]])],
                ],
                ['int_col', 'char_col', 'float_col', 'bool_col', 'json_col'],
                'expected' => static::replaceQuotes(
                    'INSERT INTO [[type]] ([[int_col]], [[char_col]], [[float_col]], [[bool_col]], [[json_col]])'
                        . ' VALUES (1, :qp0, 0, TRUE, :qp1), (2, :qp2, -1, FALSE, :qp3)'
                ),
                'expectedParams' => [
                    ':qp0' => 'a',
                    ':qp1' => '{"a":1,"b":true,"c":[1,2,3]}',

                    ':qp2' => 'b',
                    ':qp3' => '{"d":"e","f":false,"g":[4,5,null]}',
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
                static::replaceQuotes(
                    <<<SQL
                    CREATE INDEX [[name]] ON [[table]] ([[column]])
                    SQL
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                '',
                '',
                static::replaceQuotes(
                    <<<SQL
                    CREATE INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                IndexType::UNIQUE,
                '',
                static::replaceQuotes(
                    <<<SQL
                    CREATE UNIQUE INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                'FULLTEXT',
                '',
                static::replaceQuotes(
                    <<<SQL
                    CREATE FULLTEXT INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                'SPATIAL',
                '',
                static::replaceQuotes(
                    <<<SQL
                    CREATE SPATIAL INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL
                ),
            ],
            [
                '{{name}}',
                '{{table}}',
                ['column1', 'column2'],
                'BITMAP',
                '',
                static::replaceQuotes(
                    <<<SQL
                    CREATE BITMAP INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL
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
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1
                    SQL
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = :id
                SQL,
                ['id' => 1],
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1
                    SQL
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = :id
                SQL,
                ['id' => new Param('1 OR 1=1', DataType::INTEGER)],
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1
                    SQL
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = :id
                SQL,
                ['id' => null],
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = NULL
                    SQL
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = :base OR [[id]] = :basePrefix
                SQL,
                ['base' => 1, 'basePrefix' => 2],
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1 OR [[id]] = 2
                    SQL
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
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[active]] = FALSE
                    SQL
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
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] IN (1, 2)
                    SQL
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
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1
                    SQL
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[id]] = ? OR [[id]] = ?
                SQL,
                [1, 2],
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[id]] = 1 OR [[id]] = 2
                    SQL
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[customer]] WHERE [[name]] = :name
                SQL,
                ['name' => new Stringable('Alfa')],
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[customer]] WHERE [[name]] = 'Alfa'
                    SQL
                ),
            ],
            [
                <<<SQL
                SELECT * FROM [[product]] WHERE [[price]] = :price
                SQL,
                ['price' => 123.45],
                static::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[product]] WHERE [[price]] = 123.45
                    SQL
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

    public static function upsertReturning(): array
    {
        return [
            'insert' => [
                'table' => 'customer',
                'insertColumns' => ['name' => 'test_1', 'email' => 'test_1@example.com'],
                'updateColumns' => true,
                'returnColumns' => null,
                'selectCondition' => ['id' => 4],
                'expectedValues' => [
                    'id' => 4,
                    'name' => 'test_1',
                    'email' => 'test_1@example.com',
                    'address' => null,
                    'status' => 0,
                    'profile_id' => null,
                ],
            ],
            'insert from sub-query' => [
                'table' => 'customer',
                'insertColumns' => (new Query(static::getDb()))->select([
                    'name' => new Expression("'test_1'"),
                    'email' => new Expression("'test_1@example.com'"),
                ]),
                'updateColumns' => true,
                'returnColumns' => null,
                'selectCondition' => ['id' => 4],
                'expectedValues' => [
                    'id' => 4,
                    'name' => 'test_1',
                    'email' => 'test_1@example.com',
                    'address' => null,
                    'status' => 0,
                    'profile_id' => null,
                ],
            ],
            'update from inserting values' => [
                'order_item',
                ['order_id' => 1, 'item_id' => 2, 'quantity' => 3, 'subtotal' => 100],
                true,
                null,
                ['order_id' => 1, 'item_id' => 2],
                [
                    'order_id' => 1,
                    'item_id' => 2,
                    'quantity' => 3,
                    'subtotal' => 100.0,
                ],
            ],
            'update from updating values' => [
                'order_item',
                ['order_id' => 1, 'item_id' => 2, 'quantity' => 3, 'subtotal' => 100],
                ['subtotal' => new Expression('{{order_item}}.[[subtotal]] + 10')],
                null,
                ['order_id' => 1, 'item_id' => 2],
                [
                    'order_id' => 1,
                    'item_id' => 2,
                    'quantity' => 2,
                    'subtotal' => 50.0,
                ],
            ],
            'do nothing' => [
                'order_item',
                ['order_id' => 1, 'item_id' => 2, 'quantity' => 3, 'subtotal' => 100],
                false,
                null,
                ['order_id' => 1, 'item_id' => 2],
                [
                    'order_id' => 1,
                    'item_id' => 2,
                    'quantity' => 2,
                    'subtotal' => 40.0,
                ],
            ],
            'no return columns' => [
                'order_item',
                ['order_id' => 1, 'item_id' => 2, 'quantity' => 3, 'subtotal' => 100],
                true,
                [],
                [],
                [],
            ],
            'no primary keys' => [
                'type',
                ['int_col' => 3, 'char_col' => str_repeat('a', 100), 'float_col' => new Expression('1 + 1.2'), 'bool_col' => true],
                true,
                ['int_col', 'char_col', 'float_col', 'bool_col'],
                ['int_col' => 3],
                ['int_col' => 3, 'char_col' => str_repeat('a', 100), 'float_col' => 2.2, 'bool_col' => true],
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
