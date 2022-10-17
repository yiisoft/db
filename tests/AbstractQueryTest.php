<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Schema;

abstract class AbstractQueryTest extends TestCase
{
    use GetTablesAliasTrait;

    /**
     * @depends testFilterWhereWithHashFormat
     * @depends testFilterWhereWithOperatorFormat
     */
    public function testAndFilterCompare(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $result = $query->andFilterCompare('name', null);

        $this->assertInstanceOf(QueryInterface::class, $result);
        $this->assertNull($query->getWhere());

        $query->andFilterCompare('name', '');

        $this->assertNull($query->getWhere());

        $query->andFilterCompare('name', 'John Doe');
        $condition = ['=', 'name', 'John Doe'];

        $this->assertSame($condition, $query->getWhere());

        $condition = ['and', $condition, ['like', 'name', 'Doe']];
        $query->andFilterCompare('name', 'Doe', 'like');

        $this->assertSame($condition, $query->getWhere());

        $condition[] = ['>', 'rating', '9'];
        $query->andFilterCompare('rating', '>9');

        $this->assertSame($condition, $query->getWhere());

        $condition[] = ['<=', 'value', '100'];
        $query->andFilterCompare('value', '<=100');

        $this->assertSame($condition, $query->getWhere());
    }

    public function testColumn(): void
    {
        $db = $this->getConnection();

        $result = (new Query($db))
            ->select('name')
            ->from('customer')
            ->orderBy(['id' => SORT_DESC])
            ->column();

        $this->assertSame(['user3', 'user2', 'user1'], $result);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/7515}
         */
        $result = (new Query($db))->from('customer')
            ->select('name')
            ->orderBy(['id' => SORT_DESC])
            ->indexBy('id')
            ->column();

        $this->assertSame([3 => 'user3', 2 => 'user2', 1 => 'user1'], $result);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/12649}
         */
        $result = (new Query($db))->from('customer')
            ->select(['name', 'id'])
            ->orderBy(['id' => SORT_DESC])
            ->indexBy(fn ($row) => $row['id'] * 2)
            ->column();

        $this->assertSame([6 => 'user3', 4 => 'user2', 2 => 'user1'], $result);

        $result = (new Query($db))->from('customer')
            ->select(['name'])
            ->indexBy('name')
            ->orderBy(['id' => SORT_DESC])
            ->column();

        $this->assertSame(['user3' => 'user3', 'user2' => 'user2', 'user1' => 'user1'], $result);

        $result = (new Query($db))->from('customer')
            ->select(['name'])
            ->where(['id' => 10])
            ->orderBy(['id' => SORT_DESC])
            ->column();

        $this->assertSame([], $result);
    }

    public function testCount(): void
    {
        $db = $this->getConnection();

        $count = (new Query($db))->from('customer')->count('*');

        $this->assertSame(3, $count);

        $count = (new Query($db))->from('customer')->where(['status' => 2])->count('*');

        $this->assertSame(1, $count);

        $count = (new Query($db))
            ->select('[[status]], COUNT([[id]]) cnt')
            ->from('customer')
            ->groupBy('status')
            ->count('*');

        $this->assertSame(2, $count);

        /* testing that orderBy() should be ignored here as it does not affect the count anyway. */
        $count = (new Query($db))->from('customer')->orderBy('status')->count('*');

        $this->assertSame(3, $count);

        $count = (new Query($db))->from('customer')->orderBy('id')->limit(1)->count('*');

        $this->assertSame(3, $count);
    }

    public function testEmulateExecution(): void
    {
        $db = $this->getConnection();

        $this->assertGreaterThan(0, (new Query($db))->from('customer')->count('*'));

        $rows = (new Query($db))->from('customer')->emulateExecution()->all();
        $this->assertSame([], $rows);

        $row = (new Query($db))->from('customer')->emulateExecution()->one();
        $this->assertNull($row);

        $exists = (new Query($db))->from('customer')->emulateExecution()->exists();
        $this->assertFalse($exists);

        $count = (new Query($db))->from('customer')->emulateExecution()->count('*');
        $this->assertSame(0, $count);

        $sum = (new Query($db))->from('customer')->emulateExecution()->sum('id');
        $this->assertNull($sum);

        $sum = (new Query($db))->from('customer')->emulateExecution()->average('id');
        $this->assertNull($sum);

        $max = (new Query($db))->from('customer')->emulateExecution()->max('id');
        $this->assertNull($max);

        $min = (new Query($db))->from('customer')->emulateExecution()->min('id');
        $this->assertNull($min);

        $scalar = (new Query($db))->select(['id'])->from('customer')->emulateExecution()->scalar();
        $this->assertNull($scalar);

        $column = (new Query($db))->select(['id'])->from('customer')->emulateExecution()->column();
        $this->assertSame([], $column);
    }

    public function testExists(): void
    {
        $db = $this->getConnection();

        $result = (new Query($db))->from('customer')->where(['status' => 2])->exists();

        $this->assertTrue($result);

        $result = (new Query($db))->from('customer')->where(['status' => 3])->exists();

        $this->assertFalse($result);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/15355}
     */
    public function testExpressionInFrom(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))
            ->from(
                new Expression(
                    '(SELECT [[id]], [[name]], [[email]], [[address]], [[status]] FROM {{customer}}) c'
                )
            )
            ->where(['status' => 2]);

        $result = $query->one();

        $this->assertSame('user3', $result['name']);
    }

    public function testFilterHavingWithHashFormat(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->filterHaving(['id' => 0, 'title' => '   ', 'author_ids' => []]);

        $this->assertSame(['id' => 0], $query->getHaving());

        $query->andFilterHaving(['status' => null]);

        $this->assertSame(['id' => 0], $query->getHaving());

        $query->orFilterHaving(['name' => '']);

        $this->assertSame(['id' => 0], $query->getHaving());
    }

    public function testFilterHavingWithOperatorFormat(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $condition = ['like', 'name', 'Alex'];
        $query->filterHaving($condition);

        $this->assertSame($condition, $query->getHaving());

        $query->andFilterHaving(['between', 'id', null, null]);

        $this->assertSame($condition, $query->getHaving());

        $query->orFilterHaving(['not between', 'id', null, null]);

        $this->assertSame($condition, $query->getHaving());

        $query->andFilterHaving(['in', 'id', []]);

        $this->assertSame($condition, $query->getHaving());

        $query->andFilterHaving(['not in', 'id', []]);

        $this->assertSame($condition, $query->getHaving());

        $query->andFilterHaving(['like', 'id', '']);

        $this->assertSame($condition, $query->getHaving());

        $query->andFilterHaving(['or like', 'id', '']);

        $this->assertSame($condition, $query->getHaving());

        $query->andFilterHaving(['not like', 'id', '   ']);

        $this->assertSame($condition, $query->getHaving());

        $query->andFilterHaving(['or not like', 'id', null]);

        $this->assertSame($condition, $query->getHaving());

        $query->andFilterHaving(['or', ['eq', 'id', null], ['eq', 'id', []]]);

        $this->assertSame($condition, $query->getHaving());
    }

    public function testFilterRecursively(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->filterWhere(
            [
                'and',
                ['like', 'name', ''],
                ['like', 'title', ''],
                ['id' => 1],
                ['not', ['like', 'name', '']],
            ],
        );

        $this->assertSame(['and', ['id' => 1]], $query->getWhere());
    }

    public function testFilterWhereWithHashFormat(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->filterWhere(['id' => 0, 'title' => '   ', 'author_ids' => []]);

        $this->assertSame(['id' => 0], $query->getWhere());

        $query->andFilterWhere(['status' => null]);

        $this->assertSame(['id' => 0], $query->getWhere());

        $query->orFilterWhere(['name' => '']);

        $this->assertSame(['id' => 0], $query->getWhere());
    }

    public function testFilterWhereWithOperatorFormat(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $condition = ['like', 'name', 'Alex'];
        $query->filterWhere($condition);

        $this->assertSame($condition, $query->getWhere());

        $query->andFilterWhere(['between', 'id', null, null]);

        $this->assertSame($condition, $query->getWhere());

        $query->orFilterWhere(['not between', 'id', null, null]);

        $this->assertSame($condition, $query->getWhere());

        $query->andFilterWhere(['in', 'id', []]);

        $this->assertSame($condition, $query->getWhere());

        $query->andFilterWhere(['not in', 'id', []]);

        $this->assertSame($condition, $query->getWhere());

        $query->andFilterWhere(['like', 'id', '']);

        $this->assertSame($condition, $query->getWhere());

        $query->andFilterWhere(['or like', 'id', '']);

        $this->assertSame($condition, $query->getWhere());

        $query->andFilterWhere(['not like', 'id', '   ']);

        $this->assertSame($condition, $query->getWhere());

        $query->andFilterWhere(['or not like', 'id', null]);

        $this->assertSame($condition, $query->getWhere());

        $query->andFilterWhere(['or', ['eq', 'id', null], ['eq', 'id', []]]);

        $this->assertSame($condition, $query->getWhere());
    }

    public function testFrom(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->from('user');

        $this->assertSame(['user'], $query->getFrom());
    }

    public function testFromTableIsArrayWithExpression(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $tables = new Expression('(SELECT id,name FROM user) u');

        $query->from($tables);
        $from = $query->getFrom();

        $this->assertIsArray($from);
        $this->assertInstanceOf(Expression::class, $from[0]);
    }

    public function testGroup(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->groupBy('team');

        $this->assertSame(['team'], $query->getGroupBy());

        $query->addGroupBy('company');

        $this->assertSame(['team', 'company'], $query->getGroupBy());

        $query->addGroupBy('age');

        $this->assertSame(['team', 'company', 'age'], $query->getGroupBy());
    }

    public function testHaving(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->having('id = :id', [':id' => 1]);

        $this->assertSame('id = :id', $query->getHaving());
        $this->assertSame([':id' => 1], $query->getParams());

        $query->andHaving('name = :name', [':name' => 'something']);

        $this->assertSame(['and', 'id = :id', 'name = :name'], $query->getHaving());
        $this->assertSame([':id' => 1, ':name' => 'something'], $query->getParams());

        $query->orHaving('age = :age', [':age' => '30']);

        $this->assertSame(['or', ['and', 'id = :id', 'name = :name'], 'age = :age'], $query->getHaving());
        $this->assertSame([':id' => 1, ':name' => 'something', ':age' => '30'], $query->getParams());
    }

    public function testLimitOffset(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->limit(10)->offset(5);

        $this->assertSame(10, $query->getLimit());
        $this->assertSame(5, $query->getOffset());
    }

    public function testLimitOffsetWithExpression(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from('customer')->select('id')->orderBy('id');
        $query->limit(new Expression('1 + 1'))->offset(new Expression('1 + 0'));
        $result = $query->column();

        $this->assertCount(2, $result);
        $this->assertContains('2', $result);
        $this->assertContains('3', $result);
        $this->assertNotContains('1', $result);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/13745}
     */
    public function testMultipleLikeConditions(): void
    {
        $tableName = 'like_test';
        $columnName = 'col';

        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            $columnName => $db->getSchema()->createColumnSchemaBuilder(Schema::TYPE_STRING, 64),
        ])->execute();

        $db->createCommand()->batchInsert(
            $tableName,
            ['col'],
            [
                ['test0'],
                ['test\1'],
                ['test\2'],
                ['foo%'],
                ['%bar'],
                ['%baz%'],
            ]
        )->execute();

        /* Basic tests */
        $this->assertSame(1, $this->countLikeQuery($db, $tableName, $columnName, ['test0']));
        $this->assertSame(2, $this->countLikeQuery($db, $tableName, $columnName, ['test\\']));
        $this->assertSame(0, $this->countLikeQuery($db, $tableName, $columnName, ['test%']));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, ['%']));

        /* Multiple condition tests */
        $this->assertSame(
            2,
            $this->countLikeQuery($db, $tableName, $columnName, ['test0', 'test\1']),
        );
        $this->assertSame(
            3,
            $this->countLikeQuery($db, $tableName, $columnName, ['test0', 'test\1', 'test\2']),
        );
        $this->assertSame(
            3,
            $this->countLikeQuery($db, $tableName, $columnName, ['foo', '%ba']),
        );
    }

    public function testOne(): void
    {
        $db = $this->getConnection();

        $result = (new Query($db))->from('customer')->where(['status' => 2])->one();

        $this->assertSame('user3', $result['name']);

        $result = (new Query($db))->from('customer')->where(['status' => 3])->one();

        $this->assertNull($result);
    }

    public function testOrder(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->orderBy('team');

        $this->assertSame(['team' => SORT_ASC], $query->getOrderBy());

        $query->addOrderBy('company');

        $this->assertSame(['team' => SORT_ASC, 'company' => SORT_ASC], $query->getOrderBy());

        $query->addOrderBy('age');

        $this->assertSame(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_ASC], $query->getOrderBy());

        $query->addOrderBy(['age' => SORT_DESC]);

        $this->assertSame(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_DESC], $query->getOrderBy());

        $query->addOrderBy('age ASC, company DESC');

        $this->assertSame(['team' => SORT_ASC, 'company' => SORT_DESC, 'age' => SORT_ASC], $query->getOrderBy());

        $expression = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');
        $query->orderBy($expression);

        $this->assertEquals([$expression], $query->getOrderBy());

        $expression = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');
        $query->addOrderBy($expression);

        $this->assertEquals([$expression, $expression], $query->getOrderBy());
    }

    public function testSelect(): void
    {
        $db = $this->getConnection();

        /* default */
        $query = new Query($db);
        $query->select('*');

        $this->assertSame(['*' => '*'], $query->getSelect());
        $this->assertNull($query->getDistinct());
        $this->assertNull($query->getSelectOption());

        $query = new Query($db);
        $query->select('id, name', 'something')->distinct(true);

        $this->assertSame(['id' => 'id', 'name' => 'name'], $query->getSelect());
        $this->assertTrue($query->getDistinct());
        $this->assertSame('something', $query->getSelectOption());

        $query = new Query($db);
        $query->addSelect('email');

        $this->assertSame(['email' => 'email'], $query->getSelect());

        $query = new Query($db);
        $query->select('id, name');
        $query->addSelect('email');

        $this->assertSame(['id' => 'id', 'name' => 'name', 'email' => 'email'], $query->getSelect());

        $query = new Query($db);
        $query->select('name, lastname');
        $query->addSelect('name');

        $this->assertSame(['name' => 'name', 'lastname' => 'lastname'], $query->getSelect());

        $query = new Query($db);
        $query->addSelect(['*', 'abc']);
        $query->addSelect(['*', 'bca']);

        $this->assertSame(['*' => '*', 'abc' => 'abc', 'bca' => 'bca'], $query->getSelect());

        $query = new Query($db);
        $query->addSelect(['field1 as a', 'field 1 as b']);

        $this->assertSame(['a' => 'field1', 'b' => 'field 1'], $query->getSelect());

        $query = new Query($db);
        $query->addSelect(['field1 a', 'field 1 b']);

        $this->assertSame(['a' => 'field1', 'b' => 'field 1'], $query->getSelect());

        $query = new Query($db);
        $query->select(['name' => 'firstname', 'lastname']);
        $query->addSelect(['firstname', 'surname' => 'lastname']);
        $query->addSelect(['firstname', 'lastname']);

        $this->assertSame(
            ['name' => 'firstname', 'lastname' => 'lastname', 'firstname' => 'firstname', 'surname' => 'lastname'],
            $query->getSelect()
        );

        $query = new Query($db);
        $query->select('name, name, name as X, name as X');

        $this->assertSame(['name' => 'name', 'X' => 'name'], $query->getSelect());

        /**
         * {@see https://github.com/yiisoft/yii2/issues/15676}
         */
        $query = (new Query($db))->select('id');

        $this->assertSame(['id' => 'id'], $query->getSelect());

        $query->select(['id', 'brand_id']);

        $this->assertSame(['id' => 'id', 'brand_id' => 'brand_id'], $query->getSelect());

        /**
         * {@see https://github.com/yiisoft/yii2/issues/15676}
         */
        $query = (new Query($db))->select(['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)']);

        $this->assertSame(['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)'], $query->getSelect());

        $query->addSelect(['LEFT(name,7) as test']);

        $this->assertSame(
            ['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)', 'test' => 'LEFT(name,7)'],
            $query->getSelect()
        );

        $query->addSelect(['LEFT(name,7) as test']);

        $this->assertSame(
            ['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)', 'test' => 'LEFT(name,7)'],
            $query->getSelect()
        );

        $query->addSelect(['test' => 'LEFT(name,7)']);

        $this->assertSame(
            ['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)', 'test' => 'LEFT(name,7)'],
            $query->getSelect()
        );

        /**
         * {@see https://github.com/yiisoft/yii2/issues/15731}
         */
        $selectedCols = [
            'total_sum' => 'SUM(f.amount)',
            'in_sum' => 'SUM(IF(f.type = :type_in, f.amount, 0))',
            'out_sum' => 'SUM(IF(f.type = :type_out, f.amount, 0))',
        ];

        $query = (new Query($db))
            ->select($selectedCols)
            ->addParams([':type_in' => 'in', ':type_out' => 'out', ':type_partner' => 'partner']);

        $this->assertSame($selectedCols, $query->getSelect());

        $query->select($selectedCols);

        $this->assertSame($selectedCols, $query->getSelect());

        /**
         * {@see https://github.com/yiisoft/yii2/issues/17384}
         */
        $query = new Query($db);

        $query->select('DISTINCT ON(tour_dates.date_from) tour_dates.date_from, tour_dates.id');

        $this->assertSame(
            ['DISTINCT ON(tour_dates.date_from) tour_dates.date_from', 'tour_dates.id' => 'tour_dates.id'],
            $query->getSelect()
        );
    }

    public function testWhere(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->where('id = :id', [':id' => 1]);

        $this->assertSame('id = :id', $query->getWhere());
        $this->assertSame([':id' => 1], $query->getParams());

        $query->andWhere('name = :name', [':name' => 'something']);

        $this->assertSame(['and', 'id = :id', 'name = :name'], $query->getWhere());
        $this->assertSame([':id' => 1, ':name' => 'something'], $query->getParams());

        $query->orWhere('age = :age', [':age' => '30']);

        $this->assertSame(['or', ['and', 'id = :id', 'name = :name'], 'age = :age'], $query->getWhere());
        $this->assertSame([':id' => 1, ':name' => 'something', ':age' => '30'], $query->getParams());
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    protected function countLikeQuery(
        ConnectionInterface $db,
        string $tableName,
        string $columnName,
        array $condition,
        string $operator = 'or'
    ): int {
        $whereCondition = [$operator];

        foreach ($condition as $value) {
            $whereCondition[] = ['like', $columnName, $value];
        }

        $result = (new Query($db))->from($tableName)->where($whereCondition)->count('*');

        if (is_numeric($result)) {
            return (int) $result;
        }

        return 0;
    }
}
