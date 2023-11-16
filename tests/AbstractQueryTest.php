<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractQueryTest extends TestCase
{
    use TestTrait;

    public function testAddGroupByExpression(): void
    {
        $db = $this->getConnection();

        $expression = new Expression('[[id]] + 1');
        $query = new Query($db);
        $query->addGroupBy($expression);

        $this->assertSame([$expression], $query->getGroupBy());
    }

    public function testAddOrderByEmpty(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->addOrderBy([]);

        $this->assertSame([], $query->getOrderBy());
    }

    public function testAddParamsWithNameInt(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->params([1 => 'value']);
        $query->addParams([2 => 'test']);

        $this->assertSame([1 => 'value', 2 => 'test'], $query->getParams());
    }

    /**
     * @depends testFilterWhereWithHashFormat
     * @depends testFilterWhereWithOperatorFormat
     *
     * @throws NotSupportedException
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

    /**
     * @throws NotSupportedException
     */
    public function testAndFilterHaving(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $result = $query->andFilterHaving(['>', 'id', 1]);

        $this->assertInstanceOf(QueryInterface::class, $result);
        $this->assertSame(['>', 'id', 1], $query->getHaving());

        $query->andFilterHaving(['>', 'id', 2]);

        $this->assertSame(['and', ['>', 'id', 1], ['>', 'id', 2]], $query->getHaving());
    }

    /**
     * @throws NotSupportedException
     */
    public function testAndFilterHavingWithHashFormat(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $result = $query->andFilterHaving(['status' => 1]);

        $this->assertInstanceOf(QueryInterface::class, $result);
        $this->assertSame(['status' => 1], $query->getHaving());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testCount(): void
    {
        $db = $this->getConnection(true);

        $count = (new Query($db))->from('customer')->count();

        $this->assertEquals(3, $count);

        $count = (new Query($db))->from('customer')->where(['status' => 2])->count();

        $this->assertEquals(1, $count);

        $count = (new Query($db))
            ->select('[[status]], COUNT([[id]]) cnt')
            ->from('customer')
            ->groupBy('status')
            ->count();

        $this->assertEquals(2, $count);

        // Testing that orderBy() should be ignored here as it does not affect the count anyway.
        $count = (new Query($db))->from('customer')->orderBy('status')->count();

        $this->assertEquals(3, $count);

        $count = (new Query($db))->from('customer')->orderBy('id')->limit(1)->count();

        $this->assertEquals(3, $count);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testEmulateExecution(): void
    {
        $db = $this->getConnection(true);

        $rows = (new Query($db))->from('customer')->emulateExecution()->all();

        $this->assertSame([], $rows);

        $row = (new Query($db))->from('customer')->emulateExecution()->one();

        $this->assertNull($row);

        $exists = (new Query($db))->from('customer')->emulateExecution()->exists();

        $this->assertFalse($exists);

        $count = (new Query($db))->from('customer')->emulateExecution()->count();

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

    /**
     * @throws NotSupportedException
     */
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

    /**
     * @throws NotSupportedException
     */
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

    /**
     * @throws NotSupportedException
     */
    public function testFilterRecursively(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->filterWhere(
            ['and', ['like', 'name', ''], ['like', 'title', ''], ['id' => 1], ['not', ['like', 'name', '']]]
        );

        $this->assertSame(['and', ['id' => 1]], $query->getWhere());
    }

    /**
     * @throws NotSupportedException
     */
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

    /**
     * @throws NotSupportedException
     */
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
        $this->assertInstanceOf(ExpressionInterface::class, $from[0]);
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

    public function testJoin(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->join('INNER JOIN', 'profile', 'user.id = profile.user_id');

        $this->assertSame([['INNER JOIN', 'profile', 'user.id = profile.user_id']], $query->getJoins());

        $query->join('LEFT JOIN', 'order', 'user.id = order.user_id');

        $this->assertSame(
            [['INNER JOIN', 'profile', 'user.id = profile.user_id'], ['LEFT JOIN', 'order', 'user.id = order.user_id']],
            $query->getJoins()
        );
    }

    public function testLimitOffset(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->limit(10)->offset(5);

        $this->assertSame(10, $query->getLimit());
        $this->assertSame(5, $query->getOffset());
    }

    /**
     * @throws NotSupportedException
     */
    public function testOrFilterHavingHashFormat(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->orFilterHaving(['status' => 1]);

        $this->assertSame(['status' => 1], $query->getHaving());
    }

    /**
     * @throws NotSupportedException
     */
    public function testOrFilterWhereHashFormat(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->orFilterWhere(['status' => 1]);

        $this->assertSame(['status' => 1], $query->getWhere());
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

        $expression1 = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');

        $query->orderBy($expression1);

        $this->assertSame([$expression1], $query->getOrderBy());

        $expression2 = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');

        $query->addOrderBy($expression2);

        $this->assertSame([$expression1, $expression2], $query->getOrderBy());
    }

    public function testRightJoin(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->rightJoin('profile', 'user.id = profile.user_id');

        $this->assertSame([['RIGHT JOIN', 'profile', 'user.id = profile.user_id']], $query->getJoins());
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
        $query->select('id, name', 'something')->distinct();

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
            $query->getSelect(),
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

    public function testSetJoin(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->setJoins(
            [
                ['INNER JOIN', 'table1', 'table1.id = table2.id'],
            ]
        );

        $this->assertSame(
            [
                ['INNER JOIN', 'table1', 'table1.id = table2.id'],
            ],
            $query->getJoins()
        );
    }

    public function testSetUnion(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->setUnions(['SELECT * FROM table1', 'SELECT * FROM table2']);

        $this->assertSame(['SELECT * FROM table1', 'SELECT * FROM table2'], $query->getUnions());
    }

    public function testShouldEmulateExecution(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $this->assertFalse($query->shouldEmulateExecution());

        $query = new Query($db);
        $query->emulateExecution();

        $this->assertTrue($query->shouldEmulateExecution());
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

    public function testWithQueries(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);
        $query->withQueries(['query1', 'query2']);

        $this->assertSame(['query1', 'query2'], $query->getWithQueries());
    }

    public function testColumnWithIndexBy(): void
    {
        $db = $this->getConnection(true);

        $query = (new Query($db))
            ->select('customer.name')
            ->from('customer')
            ->indexBy('customer.id');

        $this->assertSame([
            1 => 'user1',
            2 => 'user2',
            3 => 'user3',
        ], $query->column());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryProvider::filterConditionData
     */
    public function testFilterCondition(array|string $condition, array|string|null $expected): void
    {
        $query = (new Query($this->getConnection()));
        $this->assertNull($query->getWhere());

        $query->filterWhere($condition);
        $this->assertEquals($expected, $query->getWhere());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryProvider::normalizeOrderBy
     */
    public function testNormalizeOrderBy(array|string|Expression $columns, array|string $expected): void
    {
        $query = (new Query($this->getConnection()));
        $this->assertEquals([], $query->getOrderBy());

        $query->orderBy($columns);
        $this->assertEquals($expected, $query->getOrderBy());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryProvider::normalizeSelect
     */
    public function testNormalizeSelect(array|string|Expression $columns, array|string $expected): void
    {
        $query = (new Query($this->getConnection()));
        $this->assertEquals([], $query->getSelect());

        $query->select($columns);
        $this->assertEquals($expected, $query->getSelect());
    }

    public function testCountGreaterThanPhpIntMax(): void
    {
        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([$this->getConnection()])
            ->onlyMethods(['queryScalar'])
            ->getMock();

        $query->expects($this->once())
            ->method('queryScalar')
            ->willReturn('12345678901234567890');

        $this->assertSame('12345678901234567890', $query->count());
    }
}
