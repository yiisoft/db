<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\Conditions\BetweenColumnsCondition;
use Yiisoft\Db\QueryBuilder\Conditions\InCondition;
use Yiisoft\Db\QueryBuilder\QueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\SchemaBuilderTrait;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Mock;
use Yiisoft\Db\Tests\Support\TraversableObject;

final class BaseQueryBuilderProvider
{
    use SchemaBuilderTrait;

    public function __construct(private Mock $mock)
    {
        $this->db = $this->mock->connection();
    }

    public function addDropChecks(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_check';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static fn (QueryBuilderInterface $qb) => $qb->dropCheck($name, $tableName),
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] CHECK ([[C_not_null]] > 100)",
                static fn (QueryBuilderInterface $qb) => $qb->addCheck($name, $tableName, '[[C_not_null]] > 100'),
            ],
        ];
    }

    public function addDropForeignKeys(): array
    {
        $name = 'CN_constraints_3';
        $pkTableName = 'T_constraints_2';
        $tableName = 'T_constraints_3';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static fn (QueryBuilderInterface $qb) => $qb->dropForeignKey($name, $tableName),
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]])"
                . " REFERENCES {{{$pkTableName}}} ([[C_id_1]]) ON DELETE CASCADE ON UPDATE CASCADE",
                static fn (QueryBuilderInterface $qb) => $qb->addForeignKey(
                    $name,
                    $tableName,
                    'C_fk_id_1',
                    $pkTableName,
                    'C_id_1',
                    'CASCADE',
                    'CASCADE'
                ),
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]], [[C_fk_id_2]])"
                . " REFERENCES {{{$pkTableName}}} ([[C_id_1]], [[C_id_2]]) ON DELETE CASCADE ON UPDATE CASCADE",
                static fn (QueryBuilderInterface $qb) => $qb->addForeignKey(
                    $name,
                    $tableName,
                    'C_fk_id_1, C_fk_id_2',
                    $pkTableName,
                    'C_id_1, C_id_2',
                    'CASCADE',
                    'CASCADE'
                ),
            ],
        ];
    }

    public function addDropPrimaryKeys(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_pk';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName}}} DROP CONSTRAINT [[$name]]",
                static fn (QueryBuilderInterface $qb) => $qb->dropPrimaryKey($name, $tableName),
            ],
            'add' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]])",
                static fn (QueryBuilderInterface $qb) => $qb->addPrimaryKey($name, $tableName, 'C_id_1'),
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName}}} ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]], [[C_id_2]])",
                static fn (QueryBuilderInterface $qb) => $qb->addPrimaryKey($name, $tableName, 'C_id_1, C_id_2'),
            ],
        ];
    }

    public function addDropUniques(): array
    {
        $tableName1 = 'T_constraints_1';
        $name1 = 'CN_unique';
        $tableName2 = 'T_constraints_2';
        $name2 = 'CN_constraints_2_multi';

        return [
            'drop' => [
                "ALTER TABLE {{{$tableName1}}} DROP CONSTRAINT [[$name1]]",
                static fn (QueryBuilderInterface $qb) => $qb->dropUnique($name1, $tableName1),
            ],
            'add' => [
                "ALTER TABLE {{{$tableName1}}} ADD CONSTRAINT [[$name1]] UNIQUE ([[C_unique]])",
                static fn (QueryBuilderInterface $qb) => $qb->addUnique($name1, $tableName1, 'C_unique'),
            ],
            'add (2 columns)' => [
                "ALTER TABLE {{{$tableName2}}} ADD CONSTRAINT [[$name2]] UNIQUE ([[C_index_2_1]], [[C_index_2_2]])",
                static fn (QueryBuilderInterface $qb) => $qb->addUnique($name2, $tableName2, 'C_index_2_1, C_index_2_2'),
            ],
        ];
    }

    public function alterColumn(): array
    {
        return [
            [
                'foo1',
                'bar',
                'varchar(255)',
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` varchar(255)
                SQL,
            ],
            [
                'foo1',
                'bar',
                'SET NOT null',
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` SET NOT null
                SQL,
            ],
            [
                'foo1',
                'bar',
                'drop default',
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` drop default
                SQL,
            ],
            [
                'foo1',
                'bar',
                'reset xyz',
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` reset xyz
                SQL,
            ],
            [
                'foo1',
                'bar',
                $this->string(255),
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` string(255)
                SQL,
            ],
            [
                'foo1',
                'bar',
                'varchar(255) USING bar::varchar',
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` varchar(255) USING bar::varchar
                SQL,
            ],
            [
                'foo1',
                'bar',
                'varchar(255) using cast("bar" as varchar)',
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` varchar(255) using cast("bar" as varchar)
                SQL,
            ],
            [
                'foo1',
                'bar',
                $this->string(255)->notNull(),
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` string(255) NOT NULL
                SQL,
            ],
            [
                'foo1',
                'bar',
                $this->string(255)->null(),
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` string(255) NULL DEFAULT NULL
                SQL,
            ],
            [
                'foo1',
                'bar',
                $this->string(255)->null()->defaultValue('xxx'),
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` string(255) NULL DEFAULT 'xxx'
                SQL,
            ],
            [
                'foo1',
                'bar',
                $this->string(255)->check('char_length(bar) > 5'),
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` string(255) CHECK (char_length(bar) > 5)
                SQL,
            ],
            [
                'foo1',
                'bar',
                $this->string(255)->defaultValue(''),
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` string(255) DEFAULT ''
                SQL,
            ],
            [
                'foo1',
                'bar',
                $this->string(255)->defaultValue('AbCdE'),
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` string(255) DEFAULT 'AbCdE'
                SQL,
            ],
            [
                'foo1',
                'bar',
                $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` timestamp DEFAULT CURRENT_TIMESTAMP
                SQL,
            ],
            [
                'foo1',
                'bar',
                $this->string(30)->unique(),
                <<<SQL
                ALTER TABLE `foo1` CHANGE `bar` `bar` string(30) UNIQUE
                SQL,
            ],
        ];
    }

    public function batchInsert(): array
    {
        return [
            'simple' => [
                'customer',
                ['email', 'name', 'address'],
                [['test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine']],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[email]], [[name]], [[address]]) VALUES (:qp0, :qp1, :qp2)
                    SQL,
                    $this->mock->getDriverName(),
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
                    <<<SQL
                    INSERT INTO [[customer]] ([[address]]) VALUES (:qp0)
                    SQL,
                    $this->mock->getDriverName(),
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
                    <<<SQL
                    INSERT INTO [[customer]] () VALUES (:qp0)
                    SQL,
                    $this->mock->getDriverName(),
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
                    <<<SQL
                    INSERT INTO [[type]] ([[bool_col]], [[bool_col2]]) VALUES (:qp0, :qp1)
                    SQL,
                    $this->mock->getDriverName(),
                ),
                [
                    ':qp0' => false,
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
                    ':qp0' => false,
                ],
            ],
        ];
    }

    public function buildConditions(): array
    {
        $conditions = [
            /* empty values */
            [['like', 'name', []], '0=1', []],
            [['not like', 'name', []], '', []],
            [['or like', 'name', []], '0=1', []],
            [['or not like', 'name', []], '', []],

            /* not */
            [['not', ''], '', []],
            [['not', 'name'], 'NOT (name)', []],
            [
                [
                    'not',
                    $this->mock->query()->select('exists')->from('some_table'),
                ],
                'NOT ((SELECT [[exists]] FROM [[some_table]]))', [],
            ],

            /* and */
            [['and', '', ''], '', []],
            [['and', '', 'id=2'], 'id=2', []],
            [['and', 'id=1', 'id=2'], '(id=1) AND (id=2)', []],
            [['and', 'type=1', ['or', 'id=1', 'id=2']], '(type=1) AND ((id=1) OR (id=2))', []],
            [['and', 'id=1', new Expression('id=:qp0', [':qp0' => 2])], '(id=1) AND (id=:qp0)', [':qp0' => 2]],
            [
                [
                    'and',
                    ['expired' => false],
                    $this->mock->query()->select('count(*) > 1')->from('queue'),
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
                    $this->mock->query()->select('min_date')->from('some_table'),
                    'max_date'
                ),
                'NOW() NOT BETWEEN (SELECT [[min_date]] FROM [[some_table]]) AND [[max_date]]',
                [],
            ],
            [
                new BetweenColumnsCondition(
                    new Expression('NOW()'),
                    'NOT BETWEEN',
                    new Expression('min_date'),
                    $this->mock->query()->select('max_date')->from('some_table'),
                ),
                'NOW() NOT BETWEEN min_date AND (SELECT [[max_date]] FROM [[some_table]])',
                [],
            ],

            /* in */
            [
                ['in', 'id', [1, 2, $this->mock->query()->select('three')->from('digits')]],
                '[[id]] IN (:qp0, :qp1, (SELECT [[three]] FROM [[digits]]))',
                [':qp0' => 1, ':qp1' => 2],
            ],
            [
                ['not in', 'id', [1, 2, 3]],
                '[[id]] NOT IN (:qp0, :qp1, :qp2)',
                [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
            ],
            [
                ['in', 'id', $this->mock->query()->select('id')->from('users')->where(['active' => 1])],
                '[[id]] IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)',
                [':qp0' => 1],
            ],
            [
                ['not in', 'id', $this->mock->query()->select('id')->from('users')->where(['active' => 1])],
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
                [
                    'in',
                    new TraversableObject(['id']),
                    new TraversableObject([['id' => 1, 'name' => 'Name1'], ['id' => 2, 'name' => 'Name2']]),
                ],
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
                [
                    'in',
                    new TraversableObject(['id', 'name']),
                    new TraversableObject([['id' => 1, 'name' => 'oy'], ['id' => 2, 'name' => 'yo']]),
                ],
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
            [new InCondition([], 'in', 1), '0=1', []],
            [new InCondition([], 'in', [1]), '0=1', []],
            [new InCondition(['id', 'name'], 'in', []), '0=1', []],
            [
                new InCondition(
                    ['id'],
                    'in',
                    $this->mock->query()->select('id')->from('users')->where(['active' => 1]),
                ),
                '([[id]]) IN (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)',
                [':qp0' => 1],
            ],
            [
                new InCondition(['id', 'name'], 'in', [['id' => 1]]),
                '([[id]], [[name]]) IN ((:qp0, NULL))',
                [':qp0' => 1],
            ],
            [
                new InCondition(['id', 'name'], 'in', [['name' => 'oy']]),
                '([[id]], [[name]]) IN ((NULL, :qp0))',
                [':qp0' => 'oy'],
            ],
            [
                new InCondition(['id', 'name'], 'in', [['id' => 1, 'name' => 'oy']]),
                '([[id]], [[name]]) IN ((:qp0, :qp1))',
                [':qp0' => 1, ':qp1' => 'oy'],
            ],

            /* exists */
            [
                [
                    'exists',
                    $this->mock->query()->select('id')->from('users')->where(['active' => 1]),
                ],
                'EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)',
                [':qp0' => 1],
            ],
            [
                ['not exists', $this->mock->query()->select('id')->from('users')->where(['active' => 1])],
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
                ['=', 'date', $this->mock->query()->select('max(date)')->from('test')->where(['id' => 5])],
                '[[date]] = (SELECT max(date) FROM [[test]] WHERE [[id]]=:qp0)',
                [':qp0' => 5],
            ],
            [['=', 'a', null], '[[a]] = NULL', []],

            /* operand1 is Expression */
            [
                ['=', new Expression('date'), '2019-08-01'],
                'date = :qp0',
                [':qp0' => '2019-08-01'],
            ],
            [
                ['=', $this->mock->query()->select('COUNT(*)')->from('test')->where(['id' => 6]), 0],
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
            [['a' => null], '[[a]] IS NULL', []],

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

            /* like */
            [['like', 'a', 'b'], '[[a]] LIKE :qp0', [':qp0' => '%b%']],
            [
                ['like', 'a', new Expression(':qp0', [':qp0' => '%b%'])],
                '[[a]] LIKE :qp0',
                [':qp0' => '%b%'],
            ],
            [['like', new Expression('CONCAT(col1, col2)'), 'b'], 'CONCAT(col1, col2) LIKE :qp0', [':qp0' => '%b%']],
        ];

        return $this->replaceQuotes($conditions);
    }

    public function buildFilterCondition(): array
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

        return $this->replaceQuotes($conditions);
    }

    public function buildFrom(): array
    {
        return [
            ['test t1', '[[test]] [[t1]]'],
            ['test as t1', '[[test]] [[t1]]'],
            ['test AS t1', '[[test]] [[t1]]'],
            ['test', '[[test]]'],
        ];
    }

    public function buildWhereExists(): array
    {
        return [
            [
                'exists',
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE EXISTS (SELECT [[1]] FROM [[Website]] [[w]])
                    SQL,
                    $this->mock->getDriverName(),
                ),
            ],
            [
                'not exists',
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE NOT EXISTS (SELECT [[1]] FROM [[Website]] [[w]])
                    SQL,
                    $this->mock->getDriverName(),
                ),
            ],
        ];
    }

    public function createDropIndex(): array
    {
        $tableName = 'T_constraints_2';
        $name1 = 'CN_constraints_2_single';
        $name2 = 'CN_constraints_2_multi';

        return [
            'drop' => [
                <<<SQL
                DROP INDEX [[$name1]] ON {{{$tableName}}}
                SQL,
                static fn (QueryBuilderInterface $qb) => $qb->dropIndex($name1, $tableName),
            ],
            'create' => [
                <<<SQL
                CREATE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])
                SQL,
                static fn (QueryBuilderInterface $qb) => $qb->createIndex($name1, $tableName, 'C_index_1'),
            ],
            'create (2 columns)' => [
                <<<SQL
                CREATE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])
                SQL,
                static fn (QueryBuilderInterface $qb) => $qb->createIndex(
                    $name2,
                    $tableName,
                    'C_index_2_1,
                    C_index_2_2',
                ),
            ],
            'create unique' => [
                <<<SQL
                CREATE UNIQUE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])
                SQL,
                static fn (QueryBuilderInterface $qb) => $qb->createIndex(
                    $name1,
                    $tableName,
                    'C_index_1',
                    QueryBuilder::INDEX_UNIQUE,
                ),
            ],
            'create unique (2 columns)' => [
                <<<SQL
                CREATE UNIQUE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])
                SQL,
                static fn (QueryBuilderInterface $qb) => $qb->createIndex(
                    $name2,
                    $tableName,
                    'C_index_2_1,
                    C_index_2_2',
                    QueryBuilder::INDEX_UNIQUE,
                ),
            ],
        ];
    }

    public function delete(): array
    {
        return [
            [
                'user',
                [
                    'is_enabled' => false,
                    'power' => new Expression('WRONG_POWER()'),
                ],
                DbHelper::replaceQuotes(
                    <<<SQL
                    DELETE FROM [[user]] WHERE ([[is_enabled]]=:qp0) AND ([[power]]=WRONG_POWER())
                    SQL,
                    $this->mock->getDriverName(),
                ),
                [
                    ':qp0' => false,
                ],
            ],
        ];
    }

    public function insert(): array
    {
        return [
            'regular-values' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'silverfire',
                    'address' => 'Kyiv {{city}}, Ukraine',
                    'is_active' => false,
                    'related_id' => null,
                ],
                [],
                <<<SQL
                INSERT INTO `customer` (`email`, `name`, `address`, `is_active`, `related_id`) VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
                SQL,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                    ':qp4' => null,
                ],
            ],
            'params-and-expressions' => [
                '{{%type}}',
                [
                    '{{%type}}.[[related_id]]' => null,
                    '[[time]]' => new Expression('now()'),
                ],
                [],
                <<<SQL
                INSERT INTO {{%type}} ({{%type}}.[[related_id]], [[time]]) VALUES (:qp0, now())
                SQL,
                [
                    ':qp0' => null,
                ],
            ],
            'carry passed params' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'sergeymakinen',
                    'address' => '{{city}}',
                    'is_active' => false,
                    'related_id' => null,
                    'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                ],
                [':phBar' => 'bar'],
                <<<SQL
                INSERT INTO `customer` (`email`, `name`, `address`, `is_active`, `related_id`, `col`) VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar))
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':qp5' => null,
                    ':phFoo' => 'foo',
                ],
            ],
            'carry passed params (query)' => [
                'customer',
                $this->mock
                    ->query()
                    ->select([
                        'email',
                        'name',
                        'address',
                        'is_active',
                        'related_id',
                    ])
                    ->from('customer')
                    ->where([
                        'email' => 'test@example.com',
                        'name' => 'sergeymakinen',
                        'address' => '{{city}}',
                        'is_active' => false,
                        'related_id' => null,
                        'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                    ]),
                [':phBar' => 'bar'],
                <<<SQL
                INSERT INTO `customer` (`email`, `name`, `address`, `is_active`, `related_id`) SELECT `email`, `name`, `address`, `is_active`, `related_id` FROM `customer` WHERE (`email`=:qp1) AND (`name`=:qp2) AND (`address`=:qp3) AND (`is_active`=:qp4) AND (`related_id` IS NULL) AND (`col`=CONCAT(:phFoo, :phBar))
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':phFoo' => 'foo',
                ],
            ],
        ];
    }

    public function insertEx(): array
    {
        return [
            'regular-values' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'silverfire',
                    'address' => 'Kyiv {{city}}, Ukraine',
                    'is_active' => false,
                    'related_id' => null,
                ],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO `customer` (`email`, `name`, `address`, `is_active`, `related_id`) VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
                    SQL,
                    $this->mock->getDriverName(),
                ),
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                    ':qp4' => null,
                ],
            ],
            'params-and-expressions' => [
                '{{%type}}',
                [
                    '{{%type}}.[[related_id]]' => null,
                    '[[time]]' => new Expression('now()'),
                ],
                [],
                <<<SQL
                INSERT INTO {{%type}} ({{%type}}.[[related_id]], [[time]]) VALUES (:qp0, now())
                SQL,
                [
                    ':qp0' => null,
                ],
            ],
            'carry passed params' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'sergeymakinen',
                    'address' => '{{city}}',
                    'is_active' => false,
                    'related_id' => null,
                    'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                ],
                [':phBar' => 'bar'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO `customer` (`email`, `name`, `address`, `is_active`, `related_id`, `col`) VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar))
                    SQL,
                    $this->mock->getDriverName(),
                ),
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':qp5' => null,
                    ':phFoo' => 'foo',
                ],
            ],
            'carry passed params (query)' => [
                'customer',
                $this->mock->query()
                    ->select([
                        'email',
                        'name',
                        'address',
                        'is_active',
                        'related_id',
                    ])
                    ->from('customer')
                    ->where([
                        'email' => 'test@example.com',
                        'name' => 'sergeymakinen',
                        'address' => '{{city}}',
                        'is_active' => false,
                        'related_id' => null,
                        'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                    ]),
                [':phBar' => 'bar'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO `customer` (`email`, `name`, `address`, `is_active`, `related_id`) SELECT `email`, `name`, `address`, `is_active`, `related_id` FROM `customer` WHERE (`email`=:qp1) AND (`name`=:qp2) AND (`address`=:qp3) AND (`is_active`=:qp4) AND (`related_id` IS NULL) AND (`col`=CONCAT(:phFoo, :phBar))
                    SQL,
                    $this->mock->getDriverName(),
                ),
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':phFoo' => 'foo',
                ],
            ],
        ];
    }

    public function update(): array
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
                    <<<SQL
                    UPDATE [[customer]] SET [[status]]=:qp0, [[updated_at]]=now() WHERE [[id]]=:qp1
                    SQL,
                    $this->mock->getDriverName(),
                ),
                [
                    ':qp0' => 1,
                    ':qp1' => 100,
                ],
            ],
        ];
    }

    private function replaceQuotes(array $conditions): array
    {
        /* adjust dbms specific escaping */
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = DbHelper::replaceQuotes($condition[1], $this->mock->getDriverName());
        }

        return $conditions;
    }
}
