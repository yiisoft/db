<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\Tests\Support\DbHelper;

final class BaseCommandProvider
{
    public function addForeignKey(): array
    {
        return [
            ['test_fk_constraint_1', 'test_fk', 'int1', 'int3'],
            ['test_fk_constraint_2', 'test_fk', ['int1'], 'int3'],
            ['test_fk_constraint_3', 'test_fk', ['int1'], ['int3']],
            ['test_fk_constraint_4', 'test_fk', ['int1', 'int2'], ['int3', 'int4']],
        ];
    }

    public function addForeignKeySql(ConnectionPDOInterface $db): array
    {
        return [
            [
                'test_fk_constraint_1',
                'test_fk',
                'int1',
                'int3',
                null,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_1] FOREIGN KEY ([int1]) REFERENCES [test_fk] ([int3])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_2',
                'test_fk',
                ['int1'],
                'int3',
                null,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_2] FOREIGN KEY ([int1]) REFERENCES [test_fk] ([int3])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_3',
                'test_fk',
                ['int1'],
                ['int3'],
                null,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_3] FOREIGN KEY ([int1]) REFERENCES [test_fk] ([int3])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_4',
                'test_fk',
                ['int1'],
                ['int3'],
                'CASCADE',
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_4] FOREIGN KEY ([int1]) REFERENCES [test_fk] ([int3]) ON DELETE CASCADE
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_5',
                'test_fk',
                ['int1'],
                ['int3'],
                'CASCADE',
                'CASCADE',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_5] FOREIGN KEY ([int1]) REFERENCES [test_fk] ([int3]) ON DELETE CASCADE ON UPDATE CASCADE
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_6',
                'test_fk',
                ['int1', 'int2'],
                ['int3', 'int4'],
                null,
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_6] FOREIGN KEY ([int1], [int2]) REFERENCES [test_fk] ([int3], [int4])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_7',
                'test_fk',
                ['int1', 'int2'],
                ['int3', 'int4'],
                'CASCADE',
                null,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_7] FOREIGN KEY ([int1], [int2]) REFERENCES [test_fk] ([int3], [int4]) ON DELETE CASCADE
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_8',
                'test_fk',
                ['int1', 'int2'],
                ['int3', 'int4'],
                'CASCADE',
                'CASCADE',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_8] FOREIGN KEY ([int1], [int2]) REFERENCES [test_fk] ([int3], [int4]) ON DELETE CASCADE ON UPDATE CASCADE
                    SQL,
                    $db->getName(),
                ),
            ],
        ];
    }

    public function addPrimaryKey(): array
    {
        return [
            ['test_pk_constraint_1', 'test_pk', 'int1'],
            ['test_pk_constraint_2', 'test_pk', ['int1']],
            ['test_pk_constraint_3', 'test_pk', ['int1', 'int2']],
        ];
    }

    public function addPrimaryKeySql(ConnectionPDOInterface $db): array
    {
        return [
            [
                'test_fk_constraint_1',
                'test_fk',
                'int1',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_1] PRIMARY KEY ([int1])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_2',
                'test_fk',
                ['int1'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_2] PRIMARY KEY ([int1])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_3',
                'test_fk',
                ['int3', 'int4'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_3] PRIMARY KEY ([int3], [int4])
                    SQL,
                    $db->getName(),
                ),
            ],
        ];
    }

    public function addUnique(): array
    {
        return [
            ['test_unique_constraint_1', 'test_unique', 'int1'],
            ['test_unique_constraint_2', 'test_unique', ['int1']],
            ['test_unique_constraint_3', 'test_unique', ['int1', 'int2']],
        ];
    }

    public function addUniqueSql(ConnectionPDOInterface $db): array
    {
        return [
            [
                'test_fk_constraint_1',
                'test_fk',
                'int1',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_1] UNIQUE ([int1])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_2',
                'test_fk',
                ['int1'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_2] UNIQUE ([int1])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_3',
                'test_fk',
                ['int3', 'int4'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_3] UNIQUE ([int3], [int4])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'test_fk_constraint_3',
                'test_fk',
                ['int1', 'int2'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [test_fk] ADD CONSTRAINT [test_fk_constraint_3] UNIQUE ([int1], [int2])
                    SQL,
                    $db->getName(),
                ),
            ],
        ];
    }

    public function batchInsert(ConnectionPDOInterface $db): array
    {
        return [
            'multirow' => [
                'type',
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'values' => [
                    ['0', '0.0', 'test string', true],
                    [false, 0, 'test string2', false],
                ],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3), (:qp4, :qp5, :qp6, :qp7)
                    SQL,
                    $db->getName(),
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
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'values' => [[1.0, 1.1, 'Kyiv {{city}}, Ukraine', true]],
                /**
                 * {@see https://github.com/yiisoft/yii2/issues/11242}
                 *
                 * Make sure curly bracelets (`{{..}}`) in values will not be escaped
                 */
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    $db->getName(),
                ),
                'expectedParams' => [
                    ':qp0' => 1,
                    ':qp1' => 1.1,
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => true,
                ],
            ],
            'wrongBehavior' => [
                '{{%type}}',
                ['{{%type}}.[[int_col]]', '[[float_col]]', 'char_col', 'bool_col'],
                'values' => [['0', '0.0', 'Kyiv {{city}}, Ukraine', false]],
                /**
                 * Test covers potentially wrong behavior and marks it as expected!.
                 *
                 * In case table name or table column is passed with curly or square bracelets, QueryBuilder can not
                 * determine the table schema and typecast values properly.
                 * TODO: make it work. Impossible without BC breaking for public methods.
                 */
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[type]].[[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:qp0, :qp1, :qp2, :qp3)
                    SQL,
                    $db->getName(),
                ),
                'expectedParams' => [
                    ':qp0' => '0',
                    ':qp1' => '0.0',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                ],
            ],
            'batchInsert binds params from expression' => [
                '{{%type}}',
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                /**
                 * This example is completely useless. This feature of batchInsert is intended to be used with complex
                 * expression objects, such as JsonExpression.
                 */
                'values' => [[new Expression(':exp1', [':exp1' => 42]), 1, 'test', false]],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]]) VALUES (:exp1, :qp1, :qp2, :qp3)
                    SQL,
                    $db->getName(),
                ),
                'expectedParams' => [
                    ':exp1' => 42,
                    ':qp1' => 1.0,
                    ':qp2' => 'test',
                    ':qp3' => false,
                ],
            ],
        ];
    }

    public function createIndex(): array
    {
        return [
            ['test_idx_constraint_1', 'test_idx', 'int1'],
            ['test_idx_constraint_2', 'test_idx', ['int1']],
            ['test_idx_constraint_3', 'test_idx', ['int1', 'int2']],
        ];
    }

    public function createIndexSql(ConnectionPDOInterface $db): array
    {
        return [
            [
                'name',
                'table',
                'column',
                '',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE INDEX [[name]] ON [[table]] ([[column]])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'name',
                'table',
                ['column1', 'column2'],
                '',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'name',
                'table',
                ['column1', 'column2'],
                QueryBuilder::INDEX_UNIQUE,
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE UNIQUE INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'name',
                'table',
                ['column1', 'column2'],
                'FULLTEXT',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE FULLTEXT INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'name',
                'table',
                ['column1', 'column2'],
                'SPATIAL',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE SPATIAL INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'name',
                'table',
                ['column1', 'column2'],
                'BITMAP',
                '',
                DbHelper::replaceQuotes(
                    <<<SQL
                    CREATE BITMAP INDEX [[name]] ON [[table]] ([[column1]], [[column2]])
                    SQL,
                    $db->getName(),
                ),
            ],
        ];
    }

    public function invalidSelectColumns(): array
    {
        return [[[]], ['*'], [['*']]];
    }

    public function rawSql(): array
    {
        return [
            [
                <<<SQL
                SELECT * FROM customer WHERE id = :id
                SQL,
                [':id' => 1],
                <<<SQL
                SELECT * FROM customer WHERE id = 1
                SQL,
            ],
            [
                <<<SQL
                SELECT * FROM customer WHERE id = :id
                SQL,
                ['id' => 1],
                <<<SQL
                SELECT * FROM customer WHERE id = 1
                SQL,
            ],
            [
                <<<SQL
                SELECT * FROM customer WHERE id = :id
                SQL,
                ['id' => null],
                <<<SQL
                SELECT * FROM customer WHERE id = NULL
                SQL,
            ],
            [
                <<<SQL
                SELECT * FROM customer WHERE id = :base OR id = :basePrefix
                SQL,
                ['base' => 1, 'basePrefix' => 2],
                <<<SQL
                SELECT * FROM customer WHERE id = 1 OR id = 2
                SQL,
            ],
            /**
             * {@see https://github.com/yiisoft/yii2/issues/9268}
             */
            [
                <<<SQL
                SELECT * FROM customer WHERE active = :active
                SQL,
                [':active' => false],
                <<<SQL
                SELECT * FROM customer WHERE active = FALSE
                SQL,
            ],
            /**
             * {@see https://github.com/yiisoft/yii2/issues/15122}
             */
            [
                <<<SQL
                SELECT * FROM customer WHERE id IN (:ids)
                SQL,
                [':ids' => new Expression(implode(', ', [1, 2]))],
                <<<SQL
                SELECT * FROM customer WHERE id IN (1, 2)
                SQL,
            ],
        ];
    }

    public function update(ConnectionPDOInterface $db): array
    {
        return [
            [
                'table',
                ['name' => 'test'],
                [],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=:qp0
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'table',
                ['name' => 'test'],
                ['id' => 1],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=:qp0 WHERE [[id]]=:qp1
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'table',
                ['name' => 'test'],
                ['id' => 1],
                ['id' => 'integer'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=:qp1 WHERE [[id]]=:qp2
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'table',
                ['name' => 'test'],
                ['id' => 1],
                ['id' => 'string'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=:qp1 WHERE [[id]]=:qp2
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'table',
                ['name' => 'test'],
                ['id' => 1],
                ['id' => 'boolean'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=:qp1 WHERE [[id]]=:qp2
                    SQL,
                    $db->getName(),
                ),
            ],
            [
                'table',
                ['name' => 'test'],
                ['id' => 1],
                ['id' => 'float'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=:qp1 WHERE [[id]]=:qp2
                    SQL,
                    $db->getName(),
                ),
            ],
        ];
    }

    public function upsert(ConnectionPDOInterface $db): array
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
                        (new query($db))
                            ->select(['email', 'address', 'status' => new Expression('1')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'address1', 'status' => 1],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new query($db))
                            ->select(['email', 'address', 'status' => new Expression('2')])
                            ->from('customer')
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
                        (new query($db))
                            ->select(['email', 'address', 'status' => new Expression('1')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        ['address' => 'Moon', 'status' => 2],
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'address1', 'status' => 1],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new query($db))
                            ->select(['email', 'address', 'status' => new Expression('3')])
                            ->from('customer')
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
                        (new query($db))
                            ->select(['email', 'address', 'status' => new Expression('1')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        false,
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'address1', 'status' => 1],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new query($db))
                            ->select(['email', 'address', 'status' => new Expression('2')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        false,
                    ],
                    'expected' => ['email' => 'user1@example.com', 'address' => 'address1', 'status' => 1],
                ],
            ],
        ];
    }
}
