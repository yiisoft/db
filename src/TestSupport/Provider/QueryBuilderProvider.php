<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestSupport\Provider;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\Conditions\BetweenColumnsCondition;
use Yiisoft\Db\QueryBuilder\Conditions\InCondition;
use Yiisoft\Db\QueryBuilder\Conditions\LikeCondition;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\TestSupport\Helper\DbHelper;
use Yiisoft\Db\TestSupport\TraversableObject;

final class QueryBuilderProvider
{
    public function __construct(
        private ConnectionInterface $db,
        private string $likeEscapeCharSql = '',
        private array $likeParameterReplacements = []
    ) {
    }

    public function addDropForeignKeysProvider(): array
    {
        $name = 'CN_constraints_3';
        $pkTableName = 'T_constraints_2';
        $tableName = 'T_constraints_3';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static function (QueryBuilderInterface $qb) use ($tableName, $name) {
                    return $qb->dropForeignKey($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]])"
                . " REFERENCES {{{$pkTableName}}} ([[C_id_1]]) ON DELETE CASCADE ON UPDATE CASCADE",
                static function (QueryBuilderInterface $qb) use ($tableName, $name, $pkTableName) {
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
                static function (QueryBuilderInterface $qb) use ($tableName, $name, $pkTableName) {
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

    public function addDropPrimaryKeysProvider(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_pk';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static function (QueryBuilderInterface $qb) use ($tableName, $name) {
                    return $qb->dropPrimaryKey($name, $tableName);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]])",
                static function (QueryBuilderInterface $qb) use ($tableName, $name) {
                    return $qb->addPrimaryKey($name, $tableName, 'C_id_1');
                },
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]], [[C_id_2]])",
                static function (QueryBuilderInterface $qb) use ($tableName, $name) {
                    return $qb->addPrimaryKey($name, $tableName, 'C_id_1, C_id_2');
                },
            ],
        ];
    }

    public function addDropUniquesProvider(): array
    {
        $tableName1 = 'T_constraints_1';
        $name1 = 'CN_unique';
        $tableName2 = 'T_constraints_2';
        $name2 = 'CN_constraints_2_multi';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName1}}} DROP CONSTRAINT [[$name1]]",
                static function (QueryBuilderInterface $qb) use ($tableName1, $name1) {
                    return $qb->dropUnique($name1, $tableName1);
                },
            ],
            'add' => [
                "ALTER TABLE {{{$tableName1}}} ADD CONSTRAINT [[$name1]] UNIQUE ([[C_unique]])",
                static function (QueryBuilderInterface $qb) use ($tableName1, $name1) {
                    return $qb->addUnique($name1, $tableName1, 'C_unique');
                },
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName2}}} ADD CONSTRAINT [[$name2]] UNIQUE ([[C_index_2_1]], [[C_index_2_2]])",
                static function (QueryBuilderInterface $qb) use ($tableName2, $name2) {
                    return $qb->addUnique($name2, $tableName2, 'C_index_2_1, C_index_2_2');
                },
            ],
        ];
    }

    public function batchInsertProvider(): array
    {
        return [
            'simple' => [
                'customer',
                ['email', 'name', 'address'],
                [['test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine']],
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[customer]] ([[email]], [[name]], [[address]])'
                    . ' VALUES (:qp0, :qp1, :qp2)',
                    $this->db->getDriver()->getDriverName(),
                ),
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                ],
            ],
            'escape-danger-chars' => [
                'customer',
                ['address'],
                [["SQL-danger chars are escaped: '); --"]],
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[customer]] ([[address]]) VALUES (:qp0)',
                    $this->db->getDriver()->getDriverName(),
                ),
                [
                    ':qp0' => "SQL-danger chars are escaped: '); --",
                ],
            ],
            'customer2' => [
                'customer',
                ['address'],
                [],
                '',
            ],
            'customer3' => [
                'customer',
                [],
                [['no columns passed']],
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[customer]] () VALUES (:qp0)',
                    $this->db->getDriver()->getDriverName(),
                ),
                [
                    ':qp0' => 'no columns passed',
                ],
            ],
            'bool-false, bool2-null' => [
                'type',
                ['bool_col', 'bool_col2'],
                [[false, null]],
                'expected' => DbHelper::replaceQuotes(
                    'INSERT INTO [[type]] ([[bool_col]], [[bool_col2]]) VALUES (:qp0, :qp1)',
                    $this->db->getDriver()->getDriverName(),
                ),
                [
                    ':qp0' => 0,
                    ':qp1' => null,
                ],
            ],
            'wrong' => [
                '{{%type}}',
                ['{{%type}}.[[float_col]]', '[[time]]'],
                [[null, new Expression('now()')], [null, new Expression('now()')]],
                'expected' => 'INSERT INTO {{%type}} ({{%type}}.[[float_col]], [[time]]) VALUES (:qp0, now()), (:qp1, now())',
                [
                    ':qp0' => null,
                    ':qp1' => null,
                ],
            ],
            'bool-false, time-now()' => [
                '{{%type}}',
                ['{{%type}}.[[bool_col]]', '[[time]]'],
                [[false, new Expression('now()')]],
                'expected' => 'INSERT INTO {{%type}} ({{%type}}.[[bool_col]], [[time]]) VALUES (:qp0, now())',
                [
                    ':qp0' => null,
                ],
            ],
        ];
    }

    public function buildConditionsProvider(): array
    {
        $conditions = [
            /* empty values */
            [['like', 'name', []], '0=1', []],
            [['not like', 'name', []], '', []],
            [['or like', 'name', []], '0=1', []],
            [['or not like', 'name', []], '', []],

            /* not */
            [['not', 'name'], 'NOT (name)', []],
            [
                [
                    'not',
                    (new Query($this->db))
                        ->select('exists')
                        ->from('some_table'),
                ],
                'NOT ((SELECT [[exists]] FROM [[some_table]]))', [],
            ],

            /* and */
            [['and', 'id=1', 'id=2'], '(id=1) AND (id=2)', []],
            [['and', 'type=1', ['or', 'id=1', 'id=2']], '(type=1) AND ((id=1) OR (id=2))', []],
            [['and', 'id=1', new Expression('id=:qp0', [':qp0' => 2])], '(id=1) AND (id=:qp0)', [':qp0' => 2]],
            [
                [
                    'and',
                    ['expired' => false],
                    (new Query($this->db))
                        ->select('count(*) > 1')
                        ->from('queue'),
                ],
                '([[expired]]=:qp0) AND ((SELECT count(*) > 1 FROM [[queue]]))',
                [':qp0' => false],
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
                [],
            ],
            [
                ['between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), 123],
                '[[date]] BETWEEN (NOW() - INTERVAL 1 MONTH) AND :qp0',
                [':qp0' => 123],
            ],
            [
                ['not between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), new Expression('NOW()')],
                '[[date]] NOT BETWEEN (NOW() - INTERVAL 1 MONTH) AND NOW()',
                [],
            ],
            [
                ['not between', 'date', new Expression('(NOW() - INTERVAL 1 MONTH)'), 123],
                '[[date]] NOT BETWEEN (NOW() - INTERVAL 1 MONTH) AND :qp0',
                [':qp0' => 123],
            ],
            [
                new BetweenColumnsCondition('2018-02-11', 'BETWEEN', 'create_time', 'update_time'),
                ':qp0 BETWEEN [[create_time]] AND [[update_time]]',
                [':qp0' => '2018-02-11'],
            ],
            [
                new BetweenColumnsCondition('2018-02-11', 'NOT BETWEEN', 'NOW()', 'update_time'),
                ':qp0 NOT BETWEEN NOW() AND [[update_time]]',
                [':qp0' => '2018-02-11'],
            ],
            [
                new BetweenColumnsCondition(new Expression('NOW()'), 'BETWEEN', 'create_time', 'update_time'),
                'NOW() BETWEEN [[create_time]] AND [[update_time]]',
                [],
            ],
            [
                new BetweenColumnsCondition(new Expression('NOW()'), 'NOT BETWEEN', 'create_time', 'update_time'),
                'NOW() NOT BETWEEN [[create_time]] AND [[update_time]]',
                [],
            ],
            [
                new BetweenColumnsCondition(
                    new Expression('NOW()'),
                    'NOT BETWEEN',
                    (new Query($this->db))
                        ->select('min_date')
                        ->from('some_table'),
                    'max_date'
                ),
                'NOW() NOT BETWEEN (SELECT [[min_date]] FROM [[some_table]]) AND [[max_date]]',
                [],
            ],

            /* in */
            [
                [
                    'in',
                    'id',
                    [
                        1,
                        2,
                        (new Query($this->db))
                            ->select('three')
                            ->from('digits'),
                    ],
                ],
                '[[id]] IN (:qp0, :qp1, (SELECT [[three]] FROM [[digits]]))',
                [':qp0' => 1, ':qp1' => 2],
            ],
            [
                ['not in', 'id', [1, 2, 3]],
                '[[id]] NOT IN (:qp0, :qp1, :qp2)',
                [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
            ],
            [
                [
                    'in',
                    'id',
                    (new Query($this->db))
                        ->select('id')
                        ->from('users')
                        ->where(['active' => 1]),
                ],
                '[[id]] IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)',
                [':qp0' => 1],
            ],
            [
                [
                    'not in',
                    'id',
                    (new Query($this->db))
                        ->select('id')
                        ->from('users')
                        ->where(['active' => 1]),
                ],
                '[[id]] NOT IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)',
                [':qp0' => 1],
            ],
            [['in', 'id', 1], '[[id]]=:qp0', [':qp0' => 1]],
            [['in', 'id', [1]], '[[id]]=:qp0', [':qp0' => 1]],
            [['in', 'id', new TraversableObject([1])], '[[id]]=:qp0', [':qp0' => 1]],
            'composite in' => [
                ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
                '([[id]], [[name]]) IN ((:qp0, :qp1))',
                [':qp0' => 1, ':qp1' => 'oy'],
            ],
            'composite in (just one column)' => [
                ['in', ['id'], [['id' => 1, 'name' => 'Name1'], ['id' => 2, 'name' => 'Name2']]],
                '[[id]] IN (:qp0, :qp1)',
                [':qp0' => 1, ':qp1' => 2],
            ],
            'composite in using array objects (just one column)' => [
                ['in', new TraversableObject(['id']), new TraversableObject([
                    ['id' => 1, 'name' => 'Name1'],
                    ['id' => 2, 'name' => 'Name2'],
                ])],
                '[[id]] IN (:qp0, :qp1)',
                [':qp0' => 1, ':qp1' => 2],
            ],

            /* in using array objects. */
            [['id' => new TraversableObject([1, 2])], '[[id]] IN (:qp0, :qp1)', [':qp0' => 1, ':qp1' => 2]],
            [
                ['in', 'id', new TraversableObject([1, 2, 3])],
                '[[id]] IN (:qp0, :qp1, :qp2)',
                [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
            ],

            /* in using array objects containing null value */
            [['in', 'id', new TraversableObject([1, null])], '[[id]]=:qp0 OR [[id]] IS NULL', [':qp0' => 1]],
            [
                ['in', 'id', new TraversableObject([1, 2, null])],
                '[[id]] IN (:qp0, :qp1) OR [[id]] IS NULL', [':qp0' => 1, ':qp1' => 2],
            ],

            /* not in using array object containing null value */
            [
                ['not in', 'id', new TraversableObject([1, null])],
                '[[id]]<>:qp0 AND [[id]] IS NOT NULL', [':qp0' => 1],
            ],
            [
                ['not in', 'id', new TraversableObject([1, 2, null])],
                '[[id]] NOT IN (:qp0, :qp1) AND [[id]] IS NOT NULL',
                [':qp0' => 1, ':qp1' => 2],
            ],

            /* in using array object containing only null value */
            [['in', 'id', new TraversableObject([null])], '[[id]] IS NULL', []],
            [['not in', 'id', new TraversableObject([null])], '[[id]] IS NOT NULL', []],
            'composite in using array objects' => [
                ['in', new TraversableObject(['id', 'name']), new TraversableObject([
                    ['id' => 1, 'name' => 'oy'],
                    ['id' => 2, 'name' => 'yo'],
                ])],
                '([[id]], [[name]]) IN ((:qp0, :qp1), (:qp2, :qp3))',
                [':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2, ':qp3' => 'yo'],
            ],

            /* in object conditions */
            [new InCondition('id', 'in', 1), '[[id]]=:qp0', [':qp0' => 1]],
            [new InCondition('id', 'in', [1]), '[[id]]=:qp0', [':qp0' => 1]],
            [new InCondition('id', 'not in', 1), '[[id]]<>:qp0', [':qp0' => 1]],
            [new InCondition('id', 'not in', [1]), '[[id]]<>:qp0', [':qp0' => 1]],
            [new InCondition('id', 'in', [1, 2]), '[[id]] IN (:qp0, :qp1)', [':qp0' => 1, ':qp1' => 2]],
            [new InCondition('id', 'not in', [1, 2]), '[[id]] NOT IN (:qp0, :qp1)', [':qp0' => 1, ':qp1' => 2]],

            /* exists */
            [
                [
                    'exists',
                    (new Query($this->db))
                        ->select('id')
                        ->from('users')
                        ->where(['active' => 1]),
                ],
                'EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)',
                [':qp0' => 1],
            ],
            [
                [
                    'not exists',
                    (new Query($this->db))
                        ->select('id')
                        ->from('users')
                        ->where(['active' => 1]),
                ],
                'NOT EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)', [':qp0' => 1],
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
                '[[date]] >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
                [],
            ],
            [
                ['>=', 'date', new Expression('DATE_SUB(NOW(), INTERVAL :month MONTH)', [':month' => 2])],
                '[[date]] >= DATE_SUB(NOW(), INTERVAL :month MONTH)',
                [':month' => 2],
            ],
            [
                [
                    '=',
                    'date',
                    (new Query($this->db))
                        ->select('max(date)')
                        ->from('test')
                        ->where(['id' => 5]),
                ],
                '[[date]] = (SELECT max(date) FROM [[test]] WHERE [[id]]=:qp0)',
                [':qp0' => 5],
            ],

            /* operand1 is Expression */
            [
                ['=', new Expression('date'), '2019-08-01'],
                'date = :qp0',
                [':qp0' => '2019-08-01'],
            ],
            [
                [
                    '=',
                    (new Query($this->db))
                        ->select('COUNT(*)')
                        ->from('test')
                        ->where(['id' => 6]),
                    0,
                ],
                '(SELECT COUNT(*) FROM [[test]] WHERE [[id]]=:qp0) = :qp1',
                [':qp0' => 6, ':qp1' => 0],
            ],

            /* hash condition */
            [['a' => 1, 'b' => 2], '([[a]]=:qp0) AND ([[b]]=:qp1)', [':qp0' => 1, ':qp1' => 2]],
            [
                ['a' => new Expression('CONCAT(col1, col2)'), 'b' => 2],
                '([[a]]=CONCAT(col1, col2)) AND ([[b]]=:qp0)',
                [':qp0' => 2],
            ],

            /* direct conditions */
            ['a = CONCAT(col1, col2)', 'a = CONCAT(col1, col2)', []],
            [
                new Expression('a = CONCAT(col1, :param1)', ['param1' => 'value1']),
                'a = CONCAT(col1, :param1)',
                ['param1' => 'value1'],
            ],

            /* Expression with params as operand of 'not' */
            [
                ['not', new Expression('any_expression(:a)', [':a' => 1])],
                'NOT (any_expression(:a))', [':a' => 1],
            ],
            [new Expression('NOT (any_expression(:a))', [':a' => 1]), 'NOT (any_expression(:a))', [':a' => 1]],
        ];

        $conditions = match ($this->db->getDriver()->getDriverName()) {
            'sqlsrv', 'sqlite' => array_merge($conditions, [
                [
                    ['in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                    '(([[id]] = :qp0 AND [[name]] = :qp1) OR ([[id]] = :qp2 AND [[name]] = :qp3))',
                    [':qp0' => 1,
                        ':qp1' => 'foo',
                        ':qp2' => 2,
                        ':qp3' => 'bar',],
                ],
                [
                    ['not in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                    '(([[id]] != :qp0 OR [[name]] != :qp1) AND ([[id]] != :qp2 OR [[name]] != :qp3))',
                    [':qp0' => 1,
                        ':qp1' => 'foo',
                        ':qp2' => 2,
                        ':qp3' => 'bar',],
                ],
                //[['in', ['id', 'name'], (new Query())->select(['id', 'name'])->from('users')->where(['active' => 1])], 'EXISTS (SELECT 1 FROM (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0) AS a WHERE a.[[id]] = [[id AND a.]]name[[ = ]]name`)', [':qp0' => 1] ],
                //[ ['not in', ['id', 'name'], (new Query())->select(['id', 'name'])->from('users')->where(['active' => 1])], 'NOT EXISTS (SELECT 1 FROM (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0) AS a WHERE a.[[id]] = [[id]] AND a.[[name = ]]name`)', [':qp0' => 1] ],
            ]),
            default => array_merge($conditions, [
                [
                    ['in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                    '([[id]], [[name]]) IN ((:qp0, :qp1), (:qp2, :qp3))',
                    [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar'],
                ],
                [
                    ['not in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                    '([[id]], [[name]]) NOT IN ((:qp0, :qp1), (:qp2, :qp3))',
                    [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar'],
                ],
                [
                    [
                        'in',
                        ['id', 'name'],
                        (new Query($this->db))
                            ->select(['id', 'name'])
                            ->from('users')
                            ->where(['active' => 1]),
                    ],
                    '([[id]], [[name]]) IN (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0)',
                    [':qp0' => 1],
                ],
                [
                    [
                        'not in',
                        ['id', 'name'],
                        (new Query($this->db))
                            ->select(['id', 'name'])
                            ->from('users')
                            ->where(['active' => 1]),
                    ],
                    '([[id]], [[name]]) NOT IN (SELECT [[id]], [[name]] FROM [[users]] WHERE [[active]]=:qp0)',
                    [':qp0' => 1],
                ],
            ]),
        };

        /* adjust dbms specific escaping */
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = DbHelper::replaceQuotes($condition[1], $this->db->getDriver()->getDriverName());
        }

        return $conditions;
    }

    public function buildExistsParamsProvider(): array
    {
        return [
            [
                'exists',
                DbHelper::replaceQuotes(
                    'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE EXISTS (SELECT [[1]] FROM [[Website]] [[w]])',
                    $this->db->getDriver()->getDriverName()
                ),
            ],
            [
                'not exists',
                DbHelper::replaceQuotes(
                    'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE NOT EXISTS (SELECT [[1]] FROM [[Website]] [[w]])',
                    $this->db->getDriver()->getDriverName()
                ),
            ],
        ];
    }

    public function buildFilterConditionProvider(): array
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
            $conditions[$i][1] = DbHelper::replaceQuotes($condition[1], $this->db->getDriver()->getDriverName());
        }

        return $conditions;
    }

    public function buildFromDataProvider(): array
    {
        return [
            ['test t1', '[[test]] [[t1]]'],
            ['test as t1', '[[test]] [[t1]]'],
            ['test AS t1', '[[test]] [[t1]]'],
            ['test', '[[test]]'],
        ];
    }

    public function buildLikeConditionsProvider(): array
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
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%'],
            ],
            [
                ['not like', 'name', ['foo%', '[abc]']],
                '[[name]] NOT LIKE :qp0 AND [[name]] NOT LIKE :qp1',
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%'],
            ],
            [
                ['or like', 'name', ['foo%', '[abc]']],
                '[[name]] LIKE :qp0 OR [[name]] LIKE :qp1',
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%'],
            ],
            [
                ['or not like', 'name', ['foo%', '[abc]']],
                '[[name]] NOT LIKE :qp0 OR [[name]] NOT LIKE :qp1',
                [':qp0' => '%foo\%%', ':qp1' => '%[abc]%'],
            ],

            /* like with Expression */
            [
                ['like', 'name', new Expression('CONCAT("test", name, "%")')],
                '[[name]] LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                ['not like', 'name', new Expression('CONCAT("test", name, "%")')],
                '[[name]] NOT LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                ['or like', 'name', new Expression('CONCAT("test", name, "%")')],
                '[[name]] LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                ['or not like', 'name', new Expression('CONCAT("test", name, "%")')],
                '[[name]] NOT LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                ['like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] LIKE CONCAT("test", name, "%") AND [[name]] LIKE :qp0',
                [':qp0' => '%\\\ab\_c%'],
            ],
            [
                ['not like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] NOT LIKE CONCAT("test", name, "%") AND [[name]] NOT LIKE :qp0',
                [':qp0' => '%\\\ab\_c%'],
            ],
            [
                ['or like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] LIKE CONCAT("test", name, "%") OR [[name]] LIKE :qp0',
                [':qp0' => '%\\\ab\_c%'],
            ],
            [
                ['or not like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] NOT LIKE CONCAT("test", name, "%") OR [[name]] NOT LIKE :qp0',
                [':qp0' => '%\\\ab\_c%'],
            ],

            /**
             * {@see https://github.com/yiisoft/yii2/issues/15630}
             */
            [
                ['like', 'location.title_ru', 'vi%', null],
                '[[location]].[[title_ru]] LIKE :qp0',
                [':qp0' => 'vi%'],
            ],

            /* like object conditions */
            [
                new LikeCondition('name', 'like', new Expression('CONCAT("test", name, "%")')),
                '[[name]] LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                new LikeCondition('name', 'not like', new Expression('CONCAT("test", name, "%")')),
                '[[name]] NOT LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                new LikeCondition('name', 'or like', new Expression('CONCAT("test", name, "%")')),
                '[[name]] LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                new LikeCondition('name', 'or not like', new Expression('CONCAT("test", name, "%")')),
                '[[name]] NOT LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                new LikeCondition('name', 'like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                '[[name]] LIKE CONCAT("test", name, "%") AND [[name]] LIKE :qp0',
                [':qp0' => '%\\\ab\_c%'],
            ],
            [
                new LikeCondition('name', 'not like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                '[[name]] NOT LIKE CONCAT("test", name, "%") AND [[name]] NOT LIKE :qp0',
                [':qp0' => '%\\\ab\_c%'],
            ],
            [
                new LikeCondition('name', 'or like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                '[[name]] LIKE CONCAT("test", name, "%") OR [[name]] LIKE :qp0', [':qp0' => '%\\\ab\_c%'],
            ],
            [
                new LikeCondition('name', 'or not like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                '[[name]] NOT LIKE CONCAT("test", name, "%") OR [[name]] NOT LIKE :qp0', [':qp0' => '%\\\ab\_c%'],
            ],

            /* like with expression as columnName */
            [['like', new Expression('name'), 'teststring'], 'name LIKE :qp0', [':qp0' => '%teststring%']],
        ];

        /* adjust dbms specific escaping */
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = DbHelper::replaceQuotes($condition[1], $this->db->getDriver()->getDriverName());
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

    public function createDropIndexesProvider(): array
    {
        $tableName = 'T_constraints_2';
        $name1 = 'CN_constraints_2_single';
        $name2 = 'CN_constraints_2_multi';

        return [
            'drop' => [
                "DROP INDEX [[$name1]] ON {{{$tableName}}}",
                static function (QueryBuilderInterface $qb) use ($tableName, $name1) {
                    return $qb->dropIndex($name1, $tableName);
                },
            ],
            'create' => [
                "CREATE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])",
                static function (QueryBuilderInterface $qb) use ($tableName, $name1) {
                    return $qb->createIndex($name1, $tableName, 'C_index_1');
                },
            ],
            'create (2 columns)' => [
                "CREATE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])",
                static function (QueryBuilderInterface $qb) use ($tableName, $name2) {
                    return $qb->createIndex($name2, $tableName, 'C_index_2_1, C_index_2_2');
                },
            ],
            'create unique' => [
                "CREATE UNIQUE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])",
                static function (QueryBuilderInterface $qb) use ($tableName, $name1) {
                    return $qb->createIndex($name1, $tableName, 'C_index_1', QueryBuilder::INDEX_UNIQUE);
                },
            ],
            'create unique (2 columns)' => [
                "CREATE UNIQUE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])",
                static function (QueryBuilderInterface $qb) use ($tableName, $name2) {
                    return $qb->createIndex($name2, $tableName, 'C_index_2_1, C_index_2_2', QueryBuilder::INDEX_UNIQUE);
                },
            ],
        ];
    }

    public function deleteProvider(): array
    {
        return [
            [
                'user',
                [
                    'is_enabled' => false,
                    'power' => new Expression('WRONG_POWER()'),
                ],
                DbHelper::replaceQuotes(
                    'DELETE FROM [[user]] WHERE ([[is_enabled]]=:qp0) AND ([[power]]=WRONG_POWER())',
                    $this->db->getDriver()->getDriverName(),
                ),
                [
                    ':qp0' => false,
                ],
            ],
        ];
    }

    public function updateProvider(): array
    {
        return [
            [
                'customer',
                [
                    'status' => 1,
                    'updated_at' => new Expression('now()'),
                ],
                [
                    'id' => 100,
                ],
                DbHelper::replaceQuotes(
                    'UPDATE [[customer]] SET [[status]]=:qp0, [[updated_at]]=now() WHERE [[id]]=:qp1',
                    $this->db->getDriver()->getDriverName(),
                ),
                [
                    ':qp0' => 1,
                    ':qp1' => 100,
                ],
            ],
        ];
    }

    public function upsertProvider(): array
    {
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
                (new Query($this->db))
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
                (new Query($this->db))
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
                (new Query($this->db))
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
                (new Query($this->db))
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
                (new Query($this->db))
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
}
