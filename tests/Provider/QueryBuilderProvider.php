<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use ArrayIterator;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\BetweenColumns;
use Yiisoft\Db\QueryBuilder\Condition\In;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\IntEnum;
use Yiisoft\Db\Tests\Support\JsonSerializableObject;
use Yiisoft\Db\Tests\Support\Stringable;
use Yiisoft\Db\Tests\Support\StringEnum;
use Yiisoft\Db\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Support\TraversableObject;

use function fopen;

/**
 * @psalm-suppress MixedAssignment
 * @psalm-suppress MixedArgument
 * @psalm-suppress PossiblyUndefinedArrayOffset
 */
class QueryBuilderProvider
{
    use TestTrait;

    protected static string $driverName = 'db';
    protected static string $likeEscapeCharSql = '';
    protected static array $likeParameterReplacements = [];

    public static function addForeignKey(): array
    {
        $name = 'CN_constraints_3';
        $pkTableName = 'T_constraints_2';
        $tableName = 'T_constraints_3';

        return [
            'add' => [
                $name,
                $tableName,
                'C_fk_id_1',
                $pkTableName,
                'C_id_1',
                ReferentialAction::CASCADE,
                ReferentialAction::CASCADE,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[$tableName]] ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]]) REFERENCES [[$pkTableName]] ([[C_id_1]]) ON DELETE CASCADE ON UPDATE CASCADE
                    SQL,
                    static::$driverName,
                ),
            ],
            'add (2 columns)' => [
                $name,
                $tableName,
                'C_fk_id_1, C_fk_id_2',
                $pkTableName,
                'C_id_1, C_id_2',
                ReferentialAction::CASCADE,
                ReferentialAction::CASCADE,
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[$tableName]] ADD CONSTRAINT [[$name]] FOREIGN KEY ([[C_fk_id_1]], [[C_fk_id_2]]) REFERENCES [[$pkTableName]] ([[C_id_1]], [[C_id_2]]) ON DELETE CASCADE ON UPDATE CASCADE
                    SQL,
                    static::$driverName,
                ),
            ],
        ];
    }

    public static function addPrimaryKey(): array
    {
        $tableName = 'T_constraints_1';
        $name = 'CN_pk';

        return [
            'add' => [
                $name,
                $tableName,
                'C_id_1',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[$tableName]] ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]])
                    SQL,
                    static::$driverName,
                ),
            ],
            'add (2 columns)' => [
                $name,
                $tableName,
                'C_id_1, C_id_2',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[$tableName]] ADD CONSTRAINT [[$name]] PRIMARY KEY ([[C_id_1]], [[C_id_2]])
                    SQL,
                    static::$driverName,
                ),
            ],
        ];
    }

    public static function addUnique(): array
    {
        $name1 = 'CN_unique';
        $tableName1 = 'T_constraints_1';
        $name2 = 'CN_constraints_2_multi';
        $tableName2 = 'T_constraints_2';

        return [
            'add' => [
                $name1,
                $tableName1,
                'C_unique_1',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[$tableName1]] ADD CONSTRAINT [[$name1]] UNIQUE ([[C_unique_1]])
                    SQL,
                    static::$driverName,
                ),
            ],
            'add (2 columns)' => [
                $name2,
                $tableName2,
                'C_unique_1, C_unique_2',
                DbHelper::replaceQuotes(
                    <<<SQL
                    ALTER TABLE [[$tableName2]] ADD CONSTRAINT [[$name2]] UNIQUE ([[C_unique_1]], [[C_unique_2]])
                    SQL,
                    static::$driverName,
                ),
            ],
        ];
    }

    public static function alterColumn(): array
    {
        return [
            [ColumnType::STRING, 'ALTER TABLE [foo1] CHANGE [bar] [bar] varchar(255)'],
        ];
    }

    public static function batchInsert(): array
    {
        return [
            'simple' => [
                'customer',
                [['test@example.com', 'silverfire', 'Kyiv {{city}}, Ukraine']],
                ['email', 'name', 'address'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[email]], [[name]], [[address]]) VALUES (:qp0, :qp1, :qp2)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => new Param('test@example.com', DataType::STRING),
                    ':qp1' => new Param('silverfire', DataType::STRING),
                    ':qp2' => new Param('Kyiv {{city}}, Ukraine', DataType::STRING),
                ],
            ],
            'escape-danger-chars' => [
                'customer',
                [["SQL-danger chars are escaped: '); --"]],
                ['address'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[address]]) VALUES (:qp0)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [':qp0' => new Param("SQL-danger chars are escaped: '); --", DataType::STRING)],
            ],
            'customer2' => [
                'customer',
                [],
                ['address'],
                'expected' => '',
            ],
            'customer3' => [
                'customer',
                [['no columns passed']],
                [],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] VALUES (:qp0)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [':qp0' => new Param('no columns passed', DataType::STRING)],
            ],
            'bool-false, bool2-null' => [
                'type',
                [[false, null]],
                ['bool_col', 'bool_col2'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[type]] ([[bool_col]], [[bool_col2]]) VALUES (FALSE, NULL)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [],
            ],
            'wrong' => [
                '{{%type}}',
                [[null, new Expression('now()')], [null, new Expression('now()')]],
                ['{{%type}}.[[float_col]]', '[[time]]'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO {{%type}} ([[float_col]], [[time]]) VALUES (NULL, now()), (NULL, now())
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [],
            ],
            'bool-false, time-now()' => [
                '{{%type}}',
                [[false, new Expression('now()')]],
                ['{{%type}}.[[bool_col]]', '[[time]]'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO {{%type}} ([[bool_col]], [[time]]) VALUES (FALSE, now())
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [],
            ],
            'column table names are not checked' => [
                '{{%type}}',
                [[true, false]],
                ['{{%type}}.[[bool_col]]', '{{%another_table}}.[[bool_col2]]'],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO {{%type}} ([[bool_col]], [[bool_col2]]) VALUES (TRUE, FALSE)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [],
            ],
            'empty-sql' => [
                '{{%type}}',
                (static function () {
                    if (false) {
                        yield [];
                    }
                })(),
                [],
                'expected' => '',
            ],
            'empty columns and non-exists table' => [
                'non_exists_table',
                [['1.0', '2', 10, 1]],
                [],
                'expected' => DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[non_exists_table]] VALUES (:qp0, :qp1, 10, 1)
                    SQL,
                    static::$driverName,
                ),
                'expectedParams' => [
                    ':qp0' => new Param('1.0', DataType::STRING),
                    ':qp1' => new Param('2', DataType::STRING),
                ],
            ],
        ];
    }

    public static function buildCondition(): array
    {
        $conditions = [
            /* empty values */
            [['like', 'name', []], '0=1', []],
            [['not like', 'name', []], '', []],
            [['or like', 'name', []], '0=1', []],
            [['or not like', 'name', []], '', []],

            /* not */
            [['not', ''], '', []],
            [['not', '0'], 'NOT (0)', []],
            [['not', 'name'], 'NOT (name)', []],
            [
                [
                    'not',
                    (new Query(static::getDb()))->select('exists')->from('some_table'),
                ],
                'NOT ((SELECT [[exists]] FROM [[some_table]]))', [],
            ],

            /* and */
            [['and', '', ''], '', []],
            [['and', '', 'id=2'], 'id=2', []],
            [['and', 'id=1', 'id=2'], '(id=1) AND (id=2)', []],
            [['and', 'type=1', ['or', 'id=1', 'id=2']], '(type=1) AND ((id=1) OR (id=2))', []],
            [['and', 'id=1', new Expression('id=:qp0', [':qp0' => 2])], '(id=1) AND (id=:qp0)', [':qp0' => 2]],
            'and-subquery' => [
                [
                    'and',
                    ['expired' => false],
                    (new Query(static::getDb()))->select('count(*) > 1')->from('queue'),
                ],
                '([[expired]] = FALSE) AND ((SELECT count(*) > 1 FROM [[queue]]))',
                [],
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
                new BetweenColumns('2018-02-11', 'BETWEEN', 'create_time', 'update_time'),
                ':qp0 BETWEEN [[create_time]] AND [[update_time]]',
                [':qp0' => '2018-02-11'],
            ],
            [
                new BetweenColumns('2018-02-11', 'NOT BETWEEN', 'NOW()', 'update_time'),
                ':qp0 NOT BETWEEN NOW() AND [[update_time]]',
                [':qp0' => '2018-02-11'],
            ],
            [
                new BetweenColumns(new Expression('NOW()'), 'BETWEEN', 'create_time', 'update_time'),
                'NOW() BETWEEN [[create_time]] AND [[update_time]]',
                [],
            ],
            [
                new BetweenColumns(new Expression('NOW()'), 'NOT BETWEEN', 'create_time', 'update_time'),
                'NOW() NOT BETWEEN [[create_time]] AND [[update_time]]',
                [],
            ],
            [
                new BetweenColumns(
                    new Expression('NOW()'),
                    'NOT BETWEEN',
                    (new Query(static::getDb()))->select('min_date')->from('some_table'),
                    'max_date'
                ),
                'NOW() NOT BETWEEN (SELECT [[min_date]] FROM [[some_table]]) AND [[max_date]]',
                [],
            ],
            [
                new BetweenColumns(
                    new Expression('NOW()'),
                    'NOT BETWEEN',
                    new Expression('min_date'),
                    (new Query(static::getDb()))->select('max_date')->from('some_table'),
                ),
                'NOW() NOT BETWEEN min_date AND (SELECT [[max_date]] FROM [[some_table]])',
                [],
            ],

            /* in */
            [
                ['in', 'id', [1, 2, (new Query(static::getDb()))->select('three')->from('digits')]],
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
                    (new Query(static::getDb()))->select('id')->from('users')->where(['active' => 1]),
                ],
                '[[id]] IN (SELECT [[id]] FROM [[users]] WHERE [[active]] = 1)',
                [],
            ],
            [
                [
                    'not in',
                    'id',
                    (new Query(static::getDb()))->select('id')->from('users')->where(['active' => 1]),
                ],
                '[[id]] NOT IN (SELECT [[id]] FROM [[users]] WHERE [[active]] = 1)',
                [],
            ],
            [['in', 'id', 1], '[[id]]=:qp0', [':qp0' => 1]],
            [['in', 'id', [1]], '[[id]]=:qp0', [':qp0' => 1]],
            [['in', 'id', new TraversableObject([1])], '[[id]]=:qp0', [':qp0' => 1]],
            'composite in' => [
                ['in', ['id', 'name'], [['id' => 1, 'name' => 'John Doe']]],
                '([[id]], [[name]]) IN ((:qp0, :qp1))',
                [':qp0' => 1, ':qp1' => 'John Doe'],
            ],
            'composite in with Expression' => [
                [
                    'in',
                    [new Expression('id'), new Expression('name')],
                    [['id' => 1, 'name' => 'John Doe']],
                ],
                '(id, name) IN ((:qp0, :qp1))',
                [':qp0' => 1, ':qp1' => 'John Doe'],
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
            [['not in', new Expression('id'), new TraversableObject([null])], '[[id]] IS NOT NULL', []],

            /* in using array object containing only null value */
            [['in', 'id', new TraversableObject([null])], '[[id]] IS NULL', []],
            [['not in', 'id', new TraversableObject([null])], '[[id]] IS NOT NULL', []],
            'composite in using array objects' => [
                [
                    'in',
                    new TraversableObject(['id', 'name']),
                    new TraversableObject([['id' => 1, 'name' => 'John Doe'], ['id' => 2, 'name' => 'yo']]),
                ],
                '([[id]], [[name]]) IN ((:qp0, :qp1), (:qp2, :qp3))',
                [':qp0' => 1, ':qp1' => 'John Doe', ':qp2' => 2, ':qp3' => 'yo'],
            ],

            /* in object conditions */
            [new In('id', 'in', 1), '[[id]]=:qp0', [':qp0' => 1]],
            [new In('id', 'in', [1]), '[[id]]=:qp0', [':qp0' => 1]],
            [new In('id', 'not in', 1), '[[id]]<>:qp0', [':qp0' => 1]],
            [new In('id', 'not in', [1]), '[[id]]<>:qp0', [':qp0' => 1]],
            [new In('id', 'in', [1, 2]), '[[id]] IN (:qp0, :qp1)', [':qp0' => 1, ':qp1' => 2]],
            [new In('id', 'not in', [1, 2]), '[[id]] NOT IN (:qp0, :qp1)', [':qp0' => 1, ':qp1' => 2]],
            [new In([], 'in', 1), '0=1', []],
            [new In([], 'in', [1]), '0=1', []],
            'inCondition-custom-1' => [new In(['id', 'name'], 'in', []), '0=1', []],
            'inCondition-custom-2' => [
                new In(
                    ['id'],
                    'in',
                    (new Query(static::getDb()))->select('id')->from('users')->where(['active' => 1]),
                ),
                '([[id]]) IN (SELECT [[id]] FROM [[users]] WHERE [[active]] = 1)',
                [],
            ],
            'inCondition-custom-3' => [
                new In(['id', 'name'], 'in', [['id' => 1]]),
                '([[id]], [[name]]) IN ((:qp0, NULL))',
                [':qp0' => 1],
            ],
            'inCondition-custom-4' => [
                new In(['id', 'name'], 'in', [['name' => 'John Doe']]),
                '([[id]], [[name]]) IN ((NULL, :qp0))',
                [':qp0' => 'John Doe'],
            ],
            'inCondition-custom-5' => [
                new In(['id', 'name'], 'in', [['id' => 1, 'name' => 'John Doe']]),
                '([[id]], [[name]]) IN ((:qp0, :qp1))',
                [':qp0' => 1, ':qp1' => 'John Doe'],
            ],
            'inCondition-custom-6' => [
                new In(
                    [new Expression('id')],
                    'in',
                    (new Query(static::getDb()))->select('id')->from('users')->where(['active' => 1]),
                ),
                '(id) IN (SELECT [[id]] FROM [[users]] WHERE [[active]] = 1)',
                [],
            ],

            /* exists */
            [
                [
                    'exists',
                    (new Query(static::getDb()))->select('id')->from('users')->where(['active' => 1]),
                ],
                'EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]] = 1)',
                [],
            ],
            [
                [
                    'not exists',
                    (new Query(static::getDb()))->select('id')->from('users')->where(['active' => 1]),
                ],
                'NOT EXISTS (SELECT [[id]] FROM [[users]] WHERE [[active]] = 1)', [],
            ],

            /* simple conditions */
            [['=', 'a', 'b'], '[[a]] = :qp0', [':qp0' => new Param('b', DataType::STRING)]],
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
                    (new Query(static::getDb()))->select('max(date)')->from('test')->where(['id' => 5]),
                ],
                '[[date]] = (SELECT max(date) FROM [[test]] WHERE [[id]] = 5)',
                [],
            ],
            [['=', 'a', null], '[[a]] IS NULL', []],

            /* operand1 is Expression */
            [
                ['=', new Expression('date'), '2019-08-01'],
                'date = :qp0',
                [':qp0' => new Param('2019-08-01', DataType::STRING)],
            ],
            [
                ['=', (new Query(static::getDb()))->select('COUNT(*)')->from('test')->where(['id' => 6]), 0],
                '(SELECT COUNT(*) FROM [[test]] WHERE [[id]] = 6) = 0',
                [],
            ],

            /* columns */
            [['a' => 1, 'b' => 2], '([[a]] = 1) AND ([[b]] = 2)', []],
            [
                ['a' => new Expression('CONCAT(col1, col2)'), 'b' => 2],
                '([[a]] = CONCAT(col1, col2)) AND ([[b]] = 2)',
                [],
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
            'like-custom-1' => [['like', 'a', 'b'], '[[a]] LIKE :qp0', [':qp0' => new Param('%b%', DataType::STRING)]],
            'like-custom-2' => [
                ['like', 'a', new Expression(':qp0', [':qp0' => '%b%'])],
                '[[a]] LIKE :qp0',
                [':qp0' => '%b%'],
            ],
            'like-custom-3' => [
                ['like', new Expression('CONCAT(col1, col2)'), 'b'], 'CONCAT(col1, col2) LIKE :qp0', [':qp0' => new Param('%b%', DataType::STRING)],
            ],

            /* json conditions */
            'search by property in JSON column' => [
                ['=', new Expression("(json_col->>'$.someKey')"), 42],
                "(json_col->>'$.someKey') = 42", [],
            ],
        ];

        /* adjust dbms specific escaping */
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = DbHelper::replaceQuotes($condition[1], static::$driverName);
        }

        return $conditions;
    }

    public static function buildFilterCondition(): array
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
            $conditions[$i][1] = DbHelper::replaceQuotes($condition[1], static::$driverName);
        }

        return $conditions;
    }

    public static function buildFrom(): array
    {
        return [
            [
                'table1',
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[table1]]
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['table1'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[table1]]
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                new Expression('table2'),
                <<<SQL
                SELECT * FROM table2
                SQL,
            ],
            [
                [new Expression('table2')],
                <<<SQL
                SELECT * FROM table2
                SQL,
            ],
            [
                ['alias' => 'table3'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[table3]] [[alias]]
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['alias' => new Expression('table4')],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM table4 [[alias]]
                    SQL,
                    static::$driverName,
                ),
            ],
            [
                ['alias' => new Expression('func(:param1, :param2)', ['param1' => 'A', 'param2' => 'B'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM func(:param1, :param2) [[alias]]
                    SQL,
                    static::$driverName,
                ),
                ['param1' => 'A', 'param2' => 'B'],
            ],
        ];
    }

    public static function buildLikeCondition(): array
    {
        $conditions = [
            /* simple like */
            [['like', 'name', 'foo%'], '[[name]] LIKE :qp0', [':qp0' => new Param('%foo\%%', DataType::STRING)]],
            [['not like', 'name', 'foo%'], '[[name]] NOT LIKE :qp0', [':qp0' => new Param('%foo\%%', DataType::STRING)]],
            [['or like', 'name', 'foo%'], '[[name]] LIKE :qp0', [':qp0' => new Param('%foo\%%', DataType::STRING)]],
            [['or not like', 'name', 'foo%'], '[[name]] NOT LIKE :qp0', [':qp0' => new Param('%foo\%%', DataType::STRING)]],

            /* like for many values */
            [
                ['like', 'name', ['foo%', '[abc]']],
                '[[name]] LIKE :qp0 AND [[name]] LIKE :qp1',
                [':qp0' => new Param('%foo\%%', DataType::STRING), ':qp1' => new Param('%[abc]%', DataType::STRING)],
            ],
            [
                ['not like', 'name', ['foo%', '[abc]']],
                '[[name]] NOT LIKE :qp0 AND [[name]] NOT LIKE :qp1',
                [':qp0' => new Param('%foo\%%', DataType::STRING), ':qp1' => new Param('%[abc]%', DataType::STRING)],
            ],
            [
                ['or like', 'name', ['foo%', '[abc]']],
                '[[name]] LIKE :qp0 OR [[name]] LIKE :qp1',
                [':qp0' => new Param('%foo\%%', DataType::STRING), ':qp1' => new Param('%[abc]%', DataType::STRING)],
            ],
            [
                ['or not like', 'name', ['foo%', '[abc]']],
                '[[name]] NOT LIKE :qp0 OR [[name]] NOT LIKE :qp1',
                [':qp0' => new Param('%foo\%%', DataType::STRING), ':qp1' => new Param('%[abc]%', DataType::STRING)],
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
                [':qp0' => new Param('%\\\ab\_c%', DataType::STRING)],
            ],
            [
                ['not like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] NOT LIKE CONCAT("test", name, "%") AND [[name]] NOT LIKE :qp0',
                [':qp0' => new Param('%\\\ab\_c%', DataType::STRING)],
            ],
            [
                ['or like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] LIKE CONCAT("test", name, "%") OR [[name]] LIKE :qp0',
                [':qp0' => new Param('%\\\ab\_c%', DataType::STRING)],
            ],
            [
                ['or not like', 'name', [new Expression('CONCAT("test", name, "%")'), '\ab_c']],
                '[[name]] NOT LIKE CONCAT("test", name, "%") OR [[name]] NOT LIKE :qp0',
                [':qp0' => new Param('%\\\ab\_c%', DataType::STRING)],
            ],

            /**
             * {@see https://github.com/yiisoft/yii2/issues/15630}
             */
            [['like', 'location.title_ru', 'vi%', null], '[[location]].[[title_ru]] LIKE :qp0', [':qp0' => new Param('vi%', DataType::STRING)]],

            /* like object conditions */
            [
                new Like('name', 'like', new Expression('CONCAT("test", name, "%")')),
                '[[name]] LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                new Like('name', 'not like', new Expression('CONCAT("test", name, "%")')),
                '[[name]] NOT LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                new Like('name', 'or like', new Expression('CONCAT("test", name, "%")')),
                '[[name]] LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                new Like('name', 'or not like', new Expression('CONCAT("test", name, "%")')),
                '[[name]] NOT LIKE CONCAT("test", name, "%")',
                [],
            ],
            [
                new Like('name', 'like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                '[[name]] LIKE CONCAT("test", name, "%") AND [[name]] LIKE :qp0',
                [':qp0' => new Param('%\\\ab\_c%', DataType::STRING)],
            ],
            [
                new Like('name', 'not like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                '[[name]] NOT LIKE CONCAT("test", name, "%") AND [[name]] NOT LIKE :qp0',
                [':qp0' => new Param('%\\\ab\_c%', DataType::STRING)],
            ],
            [
                new Like('name', 'or like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                '[[name]] LIKE CONCAT("test", name, "%") OR [[name]] LIKE :qp0', [':qp0' => new Param('%\\\ab\_c%', DataType::STRING)],
            ],
            [
                new Like('name', 'or not like', [new Expression('CONCAT("test", name, "%")'), '\ab_c']),
                '[[name]] NOT LIKE CONCAT("test", name, "%") OR [[name]] NOT LIKE :qp0', [':qp0' => new Param('%\\\ab\_c%', DataType::STRING)],
            ],

            /* like with expression as columnName */
            [['like', new Expression('name'), 'teststring'], 'name LIKE :qp0', [':qp0' => new Param('%teststring%', DataType::STRING)]],

            /* like with brackets as columnName */
            [['like', '(SELECT column_name FROM columns WHERE id=1)', 'teststring'], '(SELECT column_name FROM columns WHERE id=1) LIKE :qp0', [':qp0' => new Param('%teststring%', DataType::STRING)]],
        ];

        /* adjust dbms specific escaping */
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = DbHelper::replaceQuotes($condition[1], static::$driverName);

            if (static::$likeEscapeCharSql !== '') {
                preg_match_all('/(?P<condition>LIKE.+?)( AND| OR|$)/', $conditions[$i][1], $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $conditions[$i][1] = str_replace(
                        $match['condition'],
                        $match['condition'] . static::$likeEscapeCharSql,
                        $conditions[$i][1]
                    );
                }
            }

            foreach ($conditions[$i][2] as $name => $value) {
                $conditions[$i][2][$name] = $conditions[$i][2][$name] instanceof Param
                    ? new Param(
                        strtr($conditions[$i][2][$name]->value, static::$likeParameterReplacements),
                        DataType::STRING
                    )
                    : strtr($conditions[$i][2][$name], static::$likeParameterReplacements);
            }
        }

        return $conditions;
    }

    public static function buildWhereExists(): array
    {
        return [
            [
                'exists',
                DbHelper::replaceQuotes(
                    'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE EXISTS (SELECT [[1]] FROM [[Website]] [[w]])',
                    static::$driverName,
                ),
            ],
            [
                'not exists',
                DbHelper::replaceQuotes(
                    'SELECT [[id]] FROM [[TotalExample]] [[t]] WHERE NOT EXISTS (SELECT [[1]] FROM [[Website]] [[w]])',
                    static::$driverName,
                ),
            ],
        ];
    }

    public static function createIndex(): array
    {
        $tableName = 'T_constraints_2';
        $name1 = 'CN_constraints_2_single';
        $name2 = 'CN_constraints_2_multi';

        return [
            'create' => [
                <<<SQL
                CREATE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])
                SQL,
                static fn (QueryBuilderInterface $qb) => $qb->createIndex($tableName, $name1, 'C_index_1'),
            ],
            'create (2 columns)' => [
                <<<SQL
                CREATE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])
                SQL,
                static fn (QueryBuilderInterface $qb) => $qb->createIndex(
                    $tableName,
                    $name2,
                    'C_index_2_1,
                    C_index_2_2',
                ),
            ],
            'create unique' => [
                <<<SQL
                CREATE UNIQUE INDEX [[$name1]] ON {{{$tableName}}} ([[C_index_1]])
                SQL,
                static fn (QueryBuilderInterface $qb) => $qb->createIndex(
                    $tableName,
                    $name1,
                    'C_index_1',
                    IndexType::UNIQUE,
                ),
            ],
            'create unique (2 columns)' => [
                <<<SQL
                CREATE UNIQUE INDEX [[$name2]] ON {{{$tableName}}} ([[C_index_2_1]], [[C_index_2_2]])
                SQL,
                static fn (QueryBuilderInterface $qb) => $qb->createIndex(
                    $tableName,
                    $name2,
                    'C_index_2_1, C_index_2_2',
                    IndexType::UNIQUE,
                ),
            ],
        ];
    }

    public static function delete(): array
    {
        return [
            'base' => [
                'user',
                ['is_enabled' => false, 'power' => new Expression('WRONG_POWER()')],
                DbHelper::replaceQuotes(
                    <<<SQL
                    DELETE FROM [[user]] WHERE ([[is_enabled]] = FALSE) AND ([[power]] = WRONG_POWER())
                    SQL,
                    static::$driverName,
                ),
                [],
            ],
        ];
    }

    public static function insert(): array
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
                    INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]]) VALUES (:qp0, :qp1, :qp2, :qp3, :qp4)
                    SQL,
                    static::$driverName,
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
                ['{{%type}}.[[related_id]]' => null, 'time' => new Expression('now()')],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO {{%type}} ([[related_id]], [[time]]) VALUES (:qp0, now())
                    SQL,
                    static::$driverName,
                ),
                [':qp0' => null],
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
                    INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]], [[col]]) VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar))
                    SQL,
                    static::$driverName,
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
                (new Query(static::getDb()))
                    ->select(['email', 'name', 'address', 'is_active', 'related_id'])
                    ->from('customer')
                    ->where(
                        [
                            'email' => 'test@example.com',
                            'name' => 'sergeymakinen',
                            'address' => '{{city}}',
                            'is_active' => false,
                            'related_id' => null,
                            'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                        ],
                    ),
                [':phBar' => 'bar'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[email]], [[name]], [[address]], [[is_active]], [[related_id]]) SELECT [[email]], [[name]], [[address]], [[is_active]], [[related_id]] FROM [[customer]] WHERE ([[email]] = :qp1) AND ([[name]] = :qp2) AND ([[address]] = :qp3) AND ([[is_active]] = FALSE) AND ([[related_id]] IS NULL) AND ([[col]] = CONCAT(:phFoo, :phBar))
                    SQL,
                    static::$driverName,
                ),
                [
                    ':phBar' => 'bar',
                    ':qp1' => new Param('test@example.com', DataType::STRING),
                    ':qp2' => new Param('sergeymakinen', DataType::STRING),
                    ':qp3' => new Param('{{city}}', DataType::STRING),
                    ':phFoo' => 'foo',
                ],
            ],
            'empty columns' => [
                'customer',
                [],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] DEFAULT VALUES
                    SQL,
                    static::$driverName,
                ),
                [],
            ],
            'query' => [
                'customer',
                (new Query(static::getDb()))
                    ->select([new Expression('email as email'), new Expression('name')])
                    ->from('customer')
                    ->where(
                        [
                            'email' => 'test@example.com',
                        ],
                    ),
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[customer]] ([[email]], [[name]]) SELECT email as email, name FROM [[customer]] WHERE [[email]] = :qp0
                    SQL,
                    static::$driverName,
                ),
                [
                    ':qp0' => new Param('test@example.com', DataType::STRING),
                ],
            ],
            'json expression' => [
                'json_type',
                [
                    'json_col' => new JsonExpression(['c' => 1, 'd' => 2]),
                ],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    INSERT INTO [[json_type]] ([[json_col]]) VALUES (:qp0)
                    SQL,
                    static::$driverName,
                ),
                [
                    ':qp0' => new Param('{"c":1,"d":2}', DataType::STRING),
                ],
            ],
        ];
    }

    public static function insertReturningPks(): array
    {
        return [
            ['{{table}}', [], [], '', []],
        ];
    }

    public static function selectScalar(): array
    {
        return [
            [1, 'SELECT 1'],
            ['custom_string', DbHelper::replaceQuotes('SELECT [[custom_string]]', static::$driverName)],
            [true, 'SELECT TRUE'],
            [false, 'SELECT FALSE'],
            [12.34, 'SELECT 12.34'],
            [[1, true, 12.34], 'SELECT 1, TRUE, 12.34'],
            [
                ['a' => 1, 'b' => true, 12.34],
                DbHelper::replaceQuotes('SELECT 1 AS [[a]], TRUE AS [[b]], 12.34', static::$driverName),
            ],
        ];
    }

    public static function update(): array
    {
        return [
            [
                '{{table}}',
                ['name' => '{{test}}'],
                [],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=:qp0
                    SQL,
                    static::$driverName,
                ),
                [
                    ':qp0' => '{{test}}',
                ],
            ],
            [
                '{{table}}',
                ['name' => '{{test}}'],
                ['id' => 1],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=:qp0 WHERE [[id]] = 1
                    SQL,
                    static::$driverName,
                ),
                [
                    ':qp0' => '{{test}}',
                ],
            ],
            [
                '{{table}}',
                ['{{table}}.name' => '{{test}}'],
                ['id' => 1],
                ['id' => 'boolean'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=:qp1 WHERE [[id]] = 1
                    SQL,
                    static::$driverName,
                ),
                [
                    'id' => 'boolean',
                    ':qp1' => '{{test}}',
                ],
            ],
            [
                'customer',
                ['status' => 1, 'updated_at' => new Expression('now()')],
                ['id' => 100],
                [],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[customer]] SET [[status]]=:qp0, [[updated_at]]=now() WHERE [[id]] = 100
                    SQL,
                    static::$driverName,
                ),
                [':qp0' => 1],
            ],
            'Expressions without params' => [
                '{{product}}',
                ['name' => new Expression('UPPER([[name]])')],
                '[[name]] = :name',
                ['name' => new Expression('LOWER([[name]])')],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[name]]=UPPER([[name]]) WHERE [[name]] = LOWER([[name]])
                    SQL,
                    static::$driverName,
                ),
                [],
            ],
            'Expression with params and without params' => [
                '{{product}}',
                ['price' => new Expression('[[price]] + :val', [':val' => 1])],
                '[[start_at]] < :date',
                ['date' => new Expression('NOW()')],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[price]]=[[price]] + :val WHERE [[start_at]] < NOW()
                    SQL,
                    static::$driverName,
                ),
                [':val' => 1],
            ],
            'Expression without params and with params' => [
                '{{product}}',
                ['name' => new Expression('UPPER([[name]])')],
                '[[name]] = :name',
                ['name' => new Expression('LOWER(:val)', [':val' => 'Apple'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[name]]=UPPER([[name]]) WHERE [[name]] = LOWER(:val)
                    SQL,
                    static::$driverName,
                ),
                [':val' => 'Apple'],
            ],
            'Expressions with the same params' => [
                '{{product}}',
                ['name' => new Expression('LOWER(:val)', ['val' => 'Apple'])],
                '[[name]] != :name',
                ['name' => new Expression('UPPER(:val)', ['val' => 'Banana'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[name]]=LOWER(:val) WHERE [[name]] != UPPER(:val_0)
                    SQL,
                    static::$driverName,
                ),
                [
                    'val' => 'Apple',
                    'val_0' => 'Banana',
                ],
            ],
            'Expressions with the same params starting with and without colon' => [
                '{{product}}',
                ['name' => new Expression('LOWER(:val)', [':val' => 'Apple'])],
                '[[name]] != :name',
                ['name' => new Expression('UPPER(:val)', ['val' => 'Banana'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[name]]=LOWER(:val) WHERE [[name]] != UPPER(:val_0)
                    SQL,
                    static::$driverName,
                ),
                [
                    ':val' => 'Apple',
                    'val_0' => 'Banana',
                ],
            ],
            'Expressions with the same and different params' => [
                '{{product}}',
                ['price' => new Expression('[[price]] * :val + :val1', ['val' => 1.2, 'val1' => 2])],
                '[[name]] IN :values',
                ['values' => new Expression('(:val, :val2)', ['val' => 'Banana', 'val2' => 'Cherry'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[price]]=[[price]] * :val + :val1 WHERE [[name]] IN (:val_0, :val2)
                    SQL,
                    static::$driverName,
                ),
                [
                    'val' => 1.2,
                    'val1' => 2,
                    'val_0' => 'Banana',
                    'val2' => 'Cherry',
                ],
            ],
            'Expressions with the different params' => [
                '{{product}}',
                ['name' => new Expression('LOWER(:val)', ['val' => 'Apple'])],
                '[[name]] != :name',
                ['name' => new Expression('UPPER(:val1)', ['val1' => 'Banana'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[name]]=LOWER(:val) WHERE [[name]] != UPPER(:val1)
                    SQL,
                    static::$driverName,
                ),
                [
                    'val' => 'Apple',
                    'val1' => 'Banana',
                ],
            ],
            'Expressions with nested Expressions' => [
                '{{table}}',
                ['name' => new Expression(
                    ':val || :val_0',
                    [
                        'val' => new Expression('LOWER(:val || :val_0)', ['val' => 'A', 'val_0' => 'B']),
                        'val_0' => new Param('C', DataType::STRING),
                    ],
                )],
                '[[name]] != :val || :val_0',
                [
                    'val_0' => new Param('F', DataType::STRING),
                    'val' => new Expression('UPPER(:val || :val_0)', ['val' => 'D', 'val_0' => 'E']),
                ],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[table]] SET [[name]]=LOWER(:val_2 || :val_0_1) || :val_0_0 WHERE [[name]] != UPPER(:val_1 || :val_0_2) || :val_0
                    SQL,
                    static::$driverName,
                ),
                [
                    'val_2' => 'A',
                    'val_0_1' => 'B',
                    'val_0_0' => new Param('C', DataType::STRING),
                    'val_1' => 'D',
                    'val_0_2' => 'E',
                    'val_0' => new Param('F', DataType::STRING),
                ],
            ],
            'Expressions with indexed params' => [
                '{{product}}',
                ['name' => new Expression('LOWER(?)', ['Apple'])],
                '[[name]] != ?',
                ['Banana'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[name]]=LOWER(?) WHERE [[name]] != ?
                    SQL,
                    static::$driverName,
                ),
                // Wrong order of params
                ['Banana', 'Apple'],
            ],
            'Expressions with a string value containing a placeholder name' => [
                '{{product}}',
                ['price' => 10],
                ':val',
                [':val' => new Expression("label=':val' AND name=:val", [':val' => 'Apple'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[price]]=:qp1 WHERE label=':val' AND name=:val_0
                    SQL,
                    static::$driverName,
                ),
                [
                    ':qp1' => 10,
                    ':val_0' => 'Apple',
                ],
            ],
            'Expressions without placeholders in SQL statement' => [
                '{{product}}',
                ['price' => 10],
                ':val',
                [':val' => new Expression("label=':val'", [':val' => 'Apple'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    UPDATE [[product]] SET [[price]]=:qp1 WHERE label=':val'
                    SQL,
                    static::$driverName,
                ),
                [
                    ':qp1' => 10,
                    ':val_0' => 'Apple',
                ],
            ],
        ];
    }

    public static function upsert(): array
    {
        return [
            'regular values' => [
                'T_upsert',
                ['email' => 'test@example.com', 'address' => 'bar {{city}}', 'status' => 1, 'profile_id' => null],
                true,
                '',
                [':qp0' => 'test@example.com', ':qp1' => 'bar {{city}}', ':qp2' => 1, ':qp3' => null],
            ],
            'regular values with unique at not the first position' => [
                'T_upsert',
                ['address' => 'bar {{city}}', 'email' => 'test@example.com', 'status' => 1, 'profile_id' => null],
                true,
                '',
                [':qp0' => 'bar {{city}}', ':qp1' => 'test@example.com', ':qp2' => 1, ':qp3' => null],
            ],
            'regular values with update part' => [
                'T_upsert',
                ['email' => 'test@example.com', 'address' => 'bar {{city}}', 'status' => 1, 'profile_id' => null],
                ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('T_upsert.orders + 1')],
                '',
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
                ['email' => 'test@example.com', 'address' => 'bar {{city}}', 'status' => 1, 'profile_id' => null],
                false,
                '',
                [':qp0' => 'test@example.com', ':qp1' => 'bar {{city}}', ':qp2' => 1, ':qp3' => null],
            ],
            'query' => [
                'T_upsert',
                (new Query(static::getDb()))
                    ->select(['email', 'status' => new Expression('2')])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                true,
                '',
                [':qp0' => new Param('user1', DataType::STRING)],
            ],
            'query with update part' => [
                'T_upsert',
                (new Query(static::getDb()))
                    ->select(['email', 'status' => new Expression('2')])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('T_upsert.orders + 1')],
                '',
                [':qp0' => new Param('user1', DataType::STRING), ':qp1' => 'foo {{city}}', ':qp2' => 2],
            ],
            'query without update part' => [
                'T_upsert',
                (new Query(static::getDb()))
                    ->select(['email', 'status' => new Expression('2')])
                    ->from('customer')
                    ->where(['name' => 'user1'])
                    ->limit(1),
                false,
                '',
                [':qp0' => new Param('user1', DataType::STRING)],
            ],
            'values and expressions' => [
                '{{%T_upsert}}',
                ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CURRENT_TIMESTAMP')],
                true,
                '',
                [':qp0' => 'dynamic@example.com'],
            ],
            'values and expressions with update part' => [
                '{{%T_upsert}}',
                ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CURRENT_TIMESTAMP')],
                ['[[orders]]' => new Expression('T_upsert.orders + 1')],
                '',
                [':qp0' => 'dynamic@example.com'],
            ],
            'values and expressions without update part' => [
                'T_upsert',
                ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('CURRENT_TIMESTAMP')],
                false,
                '',
                [':qp0' => 'dynamic@example.com'],
            ],
            'query, values and expressions with update part' => [
                '{{%T_upsert}}',
                (new Query(static::getDb()))
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('CURRENT_TIMESTAMP'),
                        ],
                    ),
                ['ts' => 0, '[[orders]]' => new Expression('T_upsert.orders + 1')],
                '',
                [':phEmail' => 'dynamic@example.com', ':qp1' => 0],
            ],
            'query, values and expressions without update part' => [
                'T_upsert',
                (new Query(static::getDb()))
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('CURRENT_TIMESTAMP'),
                        ],
                    ),
                false,
                '',
                [':phEmail' => 'dynamic@example.com'],
            ],
            'no columns to update' => [
                'T_upsert_1',
                ['a' => 1],
                false,
                '',
                [':qp0' => 1],
            ],
            'no columns to update with unique' => [
                'T_upsert',
                ['email' => 'email'],
                true,
                '',
                [':qp0' => 'email'],
            ],
            'no unique columns in table - simple insert' => [
                '{{%animal}}',
                ['type' => 'test'],
                false,
                '',
                [':qp0' => 'test'],
            ],
        ];
    }

    public static function upsertReturning(): array
    {
        return [
            ['{{table}}', [], [], [], '', []],
        ];
    }

    public static function cteAliases(): array
    {
        return [
            'simple' => ['a', '[[a]]'],
            'with one column' => ['a(b)', '[[a]]([[b]])'],
            'with columns' => ['a(b,c,d)', '[[a]]([[b]], [[c]], [[d]])'],
            'with extra space' => ['a(b,c,d) ', 'a(b,c,d) '],
            'expression' => [new Expression('a(b,c,d)'), 'a(b,c,d)'],
        ];
    }

    public static function columnTypes(): array
    {
        return [
            [ColumnType::STRING],
            [ColumnBuilder::string(100)],
        ];
    }

    public static function overlapsCondition(): array
    {
        return [
            [[], 0],
            [[0], 0],
            [[1], 1],
            [[4], 1],
            [[3], 2],
            [[0, 1], 1],
            [[1, 2], 1],
            [[1, 4], 2],
            [[0, 1, 2, 3, 4, 5, 6], 2],
            [[6, 7, 8, 9], 0],
            [new ArrayIterator([0, 1, 2, 7]), 1],
            'null' => [[null], 1],
            'expression' => [new Expression("'[0,1,2,7]'"), 1],
            'json expression' => [new JsonExpression([0,1,2,7]), 1],
            'query expression' => [(new Query(static::getDb()))->select(new JsonExpression([0,1,2,7])), 1],
        ];
    }

    public static function buildColumnDefinition(): array
    {
        $reference = new ForeignKey(
            foreignTableName: 'ref_table',
            foreignColumnNames: ['id'],
            onDelete:ReferentialAction::SET_NULL,
            onUpdate: ReferentialAction::CASCADE,
        );

        $referenceWithSchema = Assert::cloneObjectWith($reference, ['foreignSchemaName' => 'ref_schema']);

        return [
            PseudoType::PK => ['integer PRIMARY KEY AUTOINCREMENT', PseudoType::PK],
            PseudoType::UPK => ['integer UNSIGNED PRIMARY KEY AUTOINCREMENT', PseudoType::UPK],
            PseudoType::BIGPK => ['bigint PRIMARY KEY AUTOINCREMENT', PseudoType::BIGPK],
            PseudoType::UBIGPK => ['bigint UNSIGNED PRIMARY KEY AUTOINCREMENT', PseudoType::UBIGPK],
            PseudoType::UUID_PK => ['uuid PRIMARY KEY DEFAULT uuid()', PseudoType::UUID_PK],
            PseudoType::UUID_PK_SEQ => ['uuid PRIMARY KEY DEFAULT uuid()', PseudoType::UUID_PK_SEQ],
            'STRING' => ['varchar(255)', ColumnType::STRING],
            'STRING(100)' => ['varchar(100)', ColumnType::STRING . '(100)'],

            'primaryKey()' => ['integer PRIMARY KEY AUTOINCREMENT', ColumnBuilder::primaryKey()],
            'primaryKey(false)' => ['integer PRIMARY KEY', ColumnBuilder::primaryKey(false)],
            'smallPrimaryKey()' => ['smallint PRIMARY KEY AUTOINCREMENT', ColumnBuilder::smallPrimaryKey()],
            'smallPrimaryKey(false)' => ['smallint PRIMARY KEY', ColumnBuilder::smallPrimaryKey(false)],
            'bigPrimaryKey()' => ['bigint PRIMARY KEY AUTOINCREMENT', ColumnBuilder::bigPrimaryKey()],
            'bigPrimaryKey(false)' => ['bigint PRIMARY KEY', ColumnBuilder::bigPrimaryKey(false)],
            'uuidPrimaryKey()' => ['uuid PRIMARY KEY DEFAULT uuid()', ColumnBuilder::uuidPrimaryKey()],
            'uuidPrimaryKey(false)' => ['uuid PRIMARY KEY', ColumnBuilder::uuidPrimaryKey(false)],

            'boolean()' => ['boolean', ColumnBuilder::boolean()],
            'boolean(100)' => ['boolean', ColumnBuilder::boolean()->size(100)],
            'bit()' => ['bit', ColumnBuilder::bit()],
            'bit(1)' => ['bit(1)', ColumnBuilder::bit(1)],
            'bit(8)' => ['bit(8)', ColumnBuilder::bit(8)],
            'bit(64)' => ['bit(64)', ColumnBuilder::bit(64)],
            'tinyint()' => ['tinyint', ColumnBuilder::tinyint()],
            'tinyint(2)' => ['tinyint(2)', ColumnBuilder::tinyint(2)],
            'smallint()' => ['smallint', ColumnBuilder::smallint()],
            'smallint(4)' => ['smallint(4)', ColumnBuilder::smallint(4)],
            'integer()' => ['integer', ColumnBuilder::integer()],
            'integer(8)' => ['integer(8)', ColumnBuilder::integer(8)],
            'bigint()' => ['bigint', ColumnBuilder::bigint()],
            'bigint(15)' => ['bigint(15)', ColumnBuilder::bigint(15)],
            'float()' => ['float', ColumnBuilder::float()],
            'float(10)' => ['float(10)', ColumnBuilder::float(10)],
            'float(10,2)' => ['float(10,2)', ColumnBuilder::float(10, 2)],
            'double()' => ['double', ColumnBuilder::double()],
            'double(10)' => ['double(10)', ColumnBuilder::double(10)],
            'double(10,2)' => ['double(10,2)', ColumnBuilder::double(10, 2)],
            'decimal()' => ['decimal(10,0)', ColumnBuilder::decimal()],
            'decimal(5)' => ['decimal(5,0)', ColumnBuilder::decimal(5)],
            'decimal(5,2)' => ['decimal(5,2)', ColumnBuilder::decimal(5, 2)],
            'decimal(null)' => ['decimal', ColumnBuilder::decimal(null)],
            'money()' => ['money', ColumnBuilder::money()],
            'money(10)' => ['money', ColumnBuilder::money(10)],
            'money(10,2)' => ['money', ColumnBuilder::money(10, 2)],
            'money(null)' => ['money', ColumnBuilder::money(null)],
            'char()' => ['char(1)', ColumnBuilder::char()],
            'char(10)' => ['char(10)', ColumnBuilder::char(10)],
            'char(null)' => ['char', ColumnBuilder::char(null)],
            'string()' => ['varchar(255)', ColumnBuilder::string()],
            'string(100)' => ['varchar(100)', ColumnBuilder::string(100)],
            'string(null)' => ['varchar(255)', ColumnBuilder::string(null)],
            'text()' => ['text', ColumnBuilder::text()],
            'text(1000)' => ['text(1000)', ColumnBuilder::text(1000)],
            'binary()' => ['binary', ColumnBuilder::binary()],
            'binary(1000)' => ['binary(1000)', ColumnBuilder::binary(1000)],
            'uuid()' => ['uuid', ColumnBuilder::uuid()],
            'timestamp()' => ['timestamp(0)', ColumnBuilder::timestamp()],
            'timestamp(6)' => ['timestamp(6)', ColumnBuilder::timestamp(6)],
            'timestamp(null)' => ['timestamp', ColumnBuilder::timestamp(null)],
            'datetime()' => ['datetime(0)', ColumnBuilder::datetime()],
            'datetime(6)' => ['datetime(6)', ColumnBuilder::datetime(6)],
            'datetime(null)' => ['datetime', ColumnBuilder::datetime(null)],
            'datetimeWithTimezone()' => ['datetimetz(0)', ColumnBuilder::datetimeWithTimezone()],
            'datetimeWithTimezone(6)' => ['datetimetz(6)', ColumnBuilder::datetimeWithTimezone(6)],
            'datetimeWithTimezone(null)' => ['datetimetz', ColumnBuilder::datetimeWithTimezone(null)],
            'time()' => ['time(0)', ColumnBuilder::time()],
            'time(6)' => ['time(6)', ColumnBuilder::time(6)],
            'time(null)' => ['time', ColumnBuilder::time(null)],
            'timeWithTimezone()' => ['timetz(0)', ColumnBuilder::timeWithTimezone()],
            'timeWithTimezone(6)' => ['timetz(6)', ColumnBuilder::timeWithTimezone(6)],
            'timeWithTimezone(null)' => ['timetz', ColumnBuilder::timeWithTimezone(null)],
            'date()' => ['date', ColumnBuilder::date()],
            'date(100)' => ['date', ColumnBuilder::date()->size(100)],
            'array()' => ['json', ColumnBuilder::array()],
            'structured()' => ['json', ColumnBuilder::structured()],
            "structured('json')" => ['json', ColumnBuilder::structured('json')],
            'json()' => ['json', ColumnBuilder::json()],
            'json(100)' => ['json', ColumnBuilder::json()->size(100)],

            "extra('NOT NULL')" => ['varchar(255) NOT NULL', ColumnBuilder::string()->extra('NOT NULL')],
            "extra('')" => ['varchar(255)', ColumnBuilder::string()->extra('')],
            "check('value > 5')" => [
                DbHelper::replaceQuotes('integer CHECK ([[check_col]] > 5)', static::$driverName),
                ColumnBuilder::integer()
                    ->withName('check_col')
                    ->check(DbHelper::replaceQuotes('[[check_col]] > 5', static::$driverName)),
            ],
            "check('')" => ['integer', ColumnBuilder::integer()->check('')],
            'check(null)' => ['integer', ColumnBuilder::integer()->check(null)],
            "comment('comment')" => ['varchar(255)', ColumnBuilder::string()->comment('comment')],
            "comment('')" => ['varchar(255)', ColumnBuilder::string()->comment('')],
            'comment(null)' => ['varchar(255)', ColumnBuilder::string()->comment(null)],
            "defaultValue('value')" => ["varchar(255) DEFAULT 'value'", ColumnBuilder::string()->defaultValue('value')],
            "defaultValue('')" => ["varchar(255) DEFAULT ''", ColumnBuilder::string()->defaultValue('')],
            'defaultValue(null)' => ['varchar(255) DEFAULT NULL', ColumnBuilder::string()->defaultValue(null)],
            'defaultValue($expression)' => ['integer DEFAULT (1 + 2)', ColumnBuilder::integer()->defaultValue(new Expression('(1 + 2)'))],
            'defaultValue($emptyExpression)' => ['integer', ColumnBuilder::integer()->defaultValue(new Expression(''))],
            "integer()->defaultValue('')" => ['integer DEFAULT NULL', ColumnBuilder::integer()->defaultValue('')],
            'notNull()' => ['varchar(255) NOT NULL', ColumnBuilder::string()->notNull()],
            'null()' => ['varchar(255) NULL', ColumnBuilder::string()->null()],
            'integer()->primaryKey()' => ['integer PRIMARY KEY', ColumnBuilder::integer()->primaryKey()],
            'string()->primaryKey()' => ['varchar(255) PRIMARY KEY', ColumnBuilder::string()->primaryKey()],
            'size(10)' => ['varchar(10)', ColumnBuilder::string()->size(10)],
            'unique()' => ['varchar(255) UNIQUE', ColumnBuilder::string()->unique()],
            'unsigned()' => ['integer UNSIGNED', ColumnBuilder::integer()->unsigned()],
            'scale(2)' => ['decimal(10,2)', ColumnBuilder::decimal()->scale(2)],
            'integer(8)->scale(2)' => ['integer(8)', ColumnBuilder::integer(8)->scale(2)],
            'reference($reference)' => [
                DbHelper::replaceQuotes(
                    <<<SQL
                    integer REFERENCES [[ref_table]] ([[id]]) ON DELETE SET NULL ON UPDATE CASCADE
                    SQL,
                    static::$driverName,
                ),
                ColumnBuilder::integer()->reference($reference),
            ],
            'reference($referenceWithSchema)' => [
                DbHelper::replaceQuotes(
                    <<<SQL
                    integer REFERENCES [[ref_schema]].[[ref_table]] ([[id]]) ON DELETE SET NULL ON UPDATE CASCADE
                    SQL,
                    static::$driverName,
                ),
                ColumnBuilder::integer()->reference($referenceWithSchema),
            ],
        ];
    }

    public static function prepareParam(): array
    {
        return [
            'null' => ['NULL', null, DataType::NULL],
            'true' => ['TRUE', true, DataType::BOOLEAN],
            'false' => ['FALSE', false, DataType::BOOLEAN],
            'integer' => ['1', 1, DataType::INTEGER],
            'integerString' => ['1', '1 or 1=1', DataType::INTEGER],
            'float' => ['1.1', 1.1, DataType::STRING],
            'string' => ["'string'", 'string', DataType::STRING],
            'binary' => ['0x737472696e67', 'string', DataType::LOB],
            'resource' => ['0x737472696e67', fopen(__DIR__ . '/../Support/string.txt', 'rb'), DataType::LOB],
            'expression' => ['(1 + 2)', new Expression('(1 + 2)'), DataType::STRING],
            'expression with params' => ['(1 + 2)', new Expression('(:a + :b)', [':a' => 1, 'b' => 2]), DataType::STRING],
            'Stringable' => ["'string'", new Stringable('string'), DataType::STRING],
            'StringEnum' => ["'one'", StringEnum::ONE, DataType::STRING],
            'IntEnum' => ['1', IntEnum::ONE, DataType::STRING],
        ];
    }

    public static function prepareValue(): array
    {
        return [
            'null' => ['NULL', null],
            'true' => ['TRUE', true],
            'false' => ['FALSE', false],
            'integer' => ['1', 1],
            'float' => ['1.1', 1.1],
            'string' => ["'string'", 'string'],
            'binary' => ['0x737472696e67', fopen(__DIR__ . '/../Support/string.txt', 'rb')],
            'paramBinary' => ['0x737472696e67', new Param('string', DataType::LOB)],
            'paramResource' => ['0x737472696e67', new Param(fopen(__DIR__ . '/../Support/string.txt', 'rb'), DataType::LOB)],
            'paramString' => ["'string'", new Param('string', DataType::STRING)],
            'paramInteger' => ['1', new Param(1, DataType::INTEGER)],
            'expression' => ['(1 + 2)', new Expression('(1 + 2)')],
            'expression with params' => ['(1 + 2)', new Expression('(:a + :b)', [':a' => 1, 'b' => 2])],
            'Stringable' => ["'string'", new Stringable('string')],
            'StringEnum' => ["'one'", StringEnum::ONE],
            'IntEnum' => ['1', IntEnum::ONE],
            'array' => ['\'["a","b","c"]\'', ['a', 'b', 'c']],
            'json' => ['\'{"a":1,"b":2}\'', ['a' => 1, 'b' => 2]],
            'Iterator' => ['\'["a","b","c"]\'', new ArrayIterator(['a', 'b', 'c'])],
            'Traversable' => ['\'{"a":1,"b":2}\'', new ArrayIterator(['a' => 1, 'b' => 2])],
            'JsonSerializable' => ['\'{"a":1,"b":2}\'', new JsonSerializableObject(['a' => 1, 'b' => 2])],
        ];
    }

    public static function buildValue(): array
    {
        return [
            'null' => [null, 'NULL'],
            'true' => [true, 'TRUE'],
            'false' => [false, 'FALSE'],
            'integer' => [1, '1'],
            'float' => [1.1, '1.1'],
            'string' => [
                'string',
                ':qp0',
                [':qp0' => new Param('string', DataType::STRING)],
            ],
            'binary' => [
                $param = fopen(__DIR__ . '/../Support/string.txt', 'rb'),
                ':qp0',
                [':qp0' => new Param($param, DataType::LOB)],
            ],
            'paramBinary' => [
                $param = new Param('string', DataType::LOB),
                ':qp0',
                [':qp0' => $param],
            ],
            'paramString' => [
                $param = new Param('string', DataType::STRING),
                ':qp0',
                [':qp0' => $param],
            ],
            'paramInteger' => [
                $param = new Param(1, DataType::INTEGER),
                ':qp0',
                [':qp0' => $param],
            ],
            'expression' => [
                new Expression('(1 + 2)'),
                '(1 + 2)',
            ],
            'expression with params' => [
                new Expression('(:a + :b)', [':a' => 1, 'b' => 2]),
                '(:a + :b)',
                [':a' => 1, 'b' => 2],
            ],
            'Stringable' => [
                new Stringable('string'),
                ':qp0',
                [':qp0' => new Param('string', DataType::STRING)],
            ],
            'StringEnum' => [
                StringEnum::ONE,
                ':qp0',
                [':qp0' => new Param('one', DataType::STRING)],
            ],
            'IntEnum' => [IntEnum::ONE, '1'],
            'array' => [
                ['a', 'b', 'c'],
                ':qp0',
                [':qp0' => new Param('["a","b","c"]', DataType::STRING)],
            ],
            'json' => [
                ['a' => 1, 'b' => 2],
                ':qp0',
                [':qp0' => new Param('{"a":1,"b":2}', DataType::STRING)],
            ],
            'Iterator' => [
                new ArrayIterator(['a', 'b', 'c']),
                ':qp0',
                [':qp0' => new Param('["a","b","c"]', DataType::STRING)],
            ],
            'Traversable' => [
                new ArrayIterator(['a' => 1, 'b' => 2]),
                ':qp0',
                [':qp0' => new Param('{"a":1,"b":2}', DataType::STRING)],
            ],
            'JsonSerializable' => [
                new JsonSerializableObject(['a' => 1, 'b' => 2]),
                ':qp0',
                [':qp0' => new Param('{"a":1,"b":2}', DataType::STRING)],
            ],
        ];
    }

    public static function caseExpressionBuilder(): array
    {
        return [
            'with case expression' => [
                (new CaseExpression('(1 + 2)'))
                    ->addWhen(1, 1)
                    ->addWhen(2, new Expression('2'))
                    ->addWhen(3, '(2 + 1)')
                    ->else($param = new Param(4, DataType::INTEGER)),
                'CASE (1 + 2) WHEN 1 THEN 1 WHEN 2 THEN 2 WHEN 3 THEN (2 + 1) ELSE :qp0 END',
                [':qp0' => $param],
                3,
            ],
            'without case expression' => [
                (new CaseExpression())
                    ->addWhen(['=', 'column_name', 1], $paramA = new Param('a', DataType::STRING))
                    ->addWhen(
                        DbHelper::replaceQuotes('[[column_name]] = 2', static::getDriverName()),
                        (new Query(self::getDb()))->select($paramB = new Param('b', DataType::STRING))
                    ),
                DbHelper::replaceQuotes(
                    <<<SQL
                    CASE WHEN [[column_name]] = 1 THEN :qp0 WHEN [[column_name]] = 2 THEN (SELECT :pv1) END
                    SQL,
                    static::getDriverName(),
                ),
                [':qp0' => $paramA, ':pv1' => $paramB],
                'b',
            ],
        ];
    }
}
