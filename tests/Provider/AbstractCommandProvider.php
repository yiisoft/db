<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\DbHelper;

use function implode;
use function len;

abstract class AbstractCommandProvider
{
    public function bindParamsNonWhere(): array
    {
        return [
            ['SELECT SUBSTR(name, :len) FROM {{customer}} WHERE [[email]] = :email GROUP BY SUBSTR(name, :len)'],
            ['SELECT SUBSTR(name, :len) FROM {{customer}} WHERE [[email]] = :email ORDER BY SUBSTR(name, :len)'],
            ['SELECT SUBSTR(name, :len) FROM {{customer}} WHERE [[email]] = :email'],
        ];
    }

    protected function getBatchInsertSql(string $driverName): array
    {
        return [
            'multirow' => [
                'type',
                ['int_col', 'float_col', 'char_col', 'bool_col'],
                'values' => [
                    ['0', '0.0', 'test string', true,],
                    [false, 0, 'test string2', false,],
                ],
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]])'
                    . ' VALUES (:qp0, :qp1, :qp2, :qp3), (:qp4, :qp5, :qp6, :qp7)',
                    $driverName,
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
                    'INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]])'
                    . ' VALUES (:qp0, :qp1, :qp2, :qp3)',
                    $driverName,
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
                    'INSERT INTO [[type]] ([[type]].[[int_col]], [[float_col]], [[char_col]], [[bool_col]])'
                    . ' VALUES (:qp0, :qp1, :qp2, :qp3)',
                    $driverName,
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
                    'INSERT INTO [[type]] ([[int_col]], [[float_col]], [[char_col]], [[bool_col]])'
                    . ' VALUES (:exp1, :qp1, :qp2, :qp3)',
                    $driverName,
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

    public function getUpsert(ConnectionInterface $db): array
    {
        return [
            'regular values' => [
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Earth',
                            'status' => '3',
                        ],
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Universe',
                            'status' => '1',
                        ],
                    ],
                ],
            ],
            'regular values with update part' => [
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Earth',
                            'status' => '3',
                        ],
                        [
                            'address' => 'Moon',
                            'status' => 2,
                        ],
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Universe',
                            'status' => 1,
                        ],
                        [
                            'address' => 'Moon',
                            'status' => 2,
                        ],
                    ],
                    'expected' => [
                        'email' => 'foo@example.com',
                        'address' => 'Moon',
                        'status' => '2',
                    ],
                ],
            ],
            'regular values without update part' => [
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Earth',
                            'status' => '3',
                        ],
                        false,
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        [
                            'email' => 'foo@example.com',
                            'address' => 'Universe',
                            'status' => 1,
                        ],
                        false,
                    ],
                    'expected' => [
                        'email' => 'foo@example.com',
                        'address' => 'Earth',
                        'status' => '3',
                    ],
                ],
            ],
            'query' => [
                [
                    'params' => [
                        'T_upsert',
                        (new Query($db))
                            ->select(['email', 'address', 'status' => new Expression('1')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => '1',
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new Query($db))
                            ->select(['email', 'address', 'status' => new Expression('2')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => '2',
                    ],
                ],
            ],
            'query with update part' => [
                [
                    'params' => [
                        'T_upsert',
                        (new Query($db))
                            ->select(['email', 'address', 'status' => new Expression('1')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        [
                            'address' => 'Moon',
                            'status' => 2,
                        ],
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => '1',
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new Query($db))
                            ->select(['email', 'address', 'status' => new Expression('3')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        [
                            'address' => 'Moon',
                            'status' => 2,
                        ],
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'Moon',
                        'status' => '2',
                    ],
                ],
            ],
            'query without update part' => [
                [
                    'params' => [
                        'T_upsert',
                        (new Query($db))
                            ->select(['email', 'address','status' => new Expression('1')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        false,
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => '1',
                    ],
                ],
                [
                    'params' => [
                        'T_upsert',
                        (new Query($db))
                            ->select(['email', 'address', 'status' => new Expression('2')])
                            ->from('customer')
                            ->where(['name' => 'user1'])
                            ->limit(1),
                        false,
                    ],
                    'expected' => [
                        'email' => 'user1@example.com',
                        'address' => 'address1',
                        'status' => '1',
                    ],
                ],
            ],
        ];
    }

    public function invalidSelectColumn(): array
    {
        return [
            [[]],
            ['*'],
            [['*']],
        ];
    }

    public function rawSql(): array
    {
        return [
            [
                'SELECT * FROM customer WHERE id = :id',
                [':id' => 1],
                'SELECT * FROM customer WHERE id = 1',
            ],
            [
                'SELECT * FROM customer WHERE id = :id',
                ['id' => 1],
                'SELECT * FROM customer WHERE id = 1',
            ],
            [
                'SELECT * FROM customer WHERE id = :id',
                ['id' => null],
                'SELECT * FROM customer WHERE id = NULL',
            ],
            [
                'SELECT * FROM customer WHERE id = :base OR id = :basePrefix',
                [
                    'base' => 1,
                    'basePrefix' => 2,
                ],
                'SELECT * FROM customer WHERE id = 1 OR id = 2',
            ],
            /**
             * {@see https://github.com/yiisoft/yii2/issues/9268}
             */
            [
                'SELECT * FROM customer WHERE active = :active',
                [':active' => false],
                'SELECT * FROM customer WHERE active = FALSE',
            ],
            /**
             * {@see https://github.com/yiisoft/yii2/issues/15122}
             */
            [
                'SELECT * FROM customer WHERE id IN (:ids)',
                [':ids' => new Expression(implode(', ', [1, 2]))],
                'SELECT * FROM customer WHERE id IN (1, 2)',
            ],
        ];
    }
}
