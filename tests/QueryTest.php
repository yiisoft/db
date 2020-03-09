<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Expressions\Expression;
use Yiisoft\Db\Querys\Query;
use Yiisoft\Db\Schemas\Schema;

abstract class QueryTest extends DatabaseTestCase
{
    public function testSelect(): void
    {
        // default
        $query = new Query($this->getConnection());
        $query->select('*');
        $this->assertEquals(['*' => '*'], $query->getSelect());
        $this->assertNull($query->getDistinct());
        $this->assertNull($query->getSelectOption());

        $query = new Query($this->getConnection());
        $query->select('id, name', 'something')->distinct(true);
        $this->assertEquals(['id' => 'id', 'name' => 'name'], $query->getSelect());
        $this->assertTrue($query->getDistinct());
        $this->assertEquals('something', $query->getSelectOption());

        $query = new Query($this->getConnection());
        $query->addSelect('email');
        $this->assertEquals(['email' => 'email'], $query->getSelect());

        $query = new Query($this->getConnection());
        $query->select('id, name');
        $query->addSelect('email');
        $this->assertEquals(['id' => 'id', 'name' => 'name', 'email' => 'email'], $query->getSelect());

        $query = new Query($this->getConnection());
        $query->select('name, lastname');
        $query->addSelect('name');
        $this->assertEquals(['name' => 'name', 'lastname' => 'lastname'], $query->getSelect());

        $query = new Query($this->getConnection());
        $query->addSelect(['*', 'abc']);
        $query->addSelect(['*', 'bca']);
        $this->assertEquals(['*' => '*', 'abc' => 'abc', 'bca' => 'bca'], $query->getSelect());

        $query = new Query($this->getConnection());
        $query->addSelect(['field1 as a', 'field 1 as b']);
        $this->assertEquals(['a' => 'field1', 'b' => 'field 1'], $query->getSelect());

        $query = new Query($this->getConnection());
        $query->addSelect(['field1 a', 'field 1 b']);
        $this->assertEquals(['a' => 'field1', 'b' => 'field 1'], $query->getSelect());

        $query = new Query($this->getConnection());
        $query->select(['name' => 'firstname', 'lastname']);
        $query->addSelect(['firstname', 'surname' => 'lastname']);
        $query->addSelect(['firstname', 'lastname']);
        $this->assertEquals(
            ['name' => 'firstname', 'lastname' => 'lastname', 'firstname' => 'firstname', 'surname' => 'lastname'],
            $query->getSelect()
        );

        $query = new Query($this->getConnection());
        $query->select('name, name, name as X, name as X');
        $this->assertEquals(['name' => 'name', 'X' => 'name'], $query->getSelect());

        /** @see https://github.com/yiisoft/yii2/issues/15676 */
        $query = (new Query($this->getConnection()))->select('id');
        $this->assertSame(['id' => 'id'], $query->getSelect());

        $query->select(['id', 'brand_id']);
        $this->assertSame(['id' => 'id', 'brand_id' => 'brand_id'], $query->getSelect());

        /** @see https://github.com/yiisoft/yii2/issues/15676 */
        $query = (new Query($this->getConnection()))
            ->select(['prefix' => 'LEFT(name, 7)', 'prefix_key' => 'LEFT(name, 7)']);

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

        /** @see https://github.com/yiisoft/yii2/issues/15731 */
        $selectedCols = [
            'total_sum' => 'SUM(f.amount)',
            'in_sum'    => 'SUM(IF(f.type = :type_in, f.amount, 0))',
            'out_sum'   => 'SUM(IF(f.type = :type_out, f.amount, 0))',
        ];
        $query = (new Query($this->getConnection()))->select($selectedCols)->addParams([
            ':type_in'      => 'in',
            ':type_out'     => 'out',
            ':type_partner' => 'partner',
        ]);
        $this->assertSame($selectedCols, $query->getSelect());

        $query->select($selectedCols);
        $this->assertSame($selectedCols, $query->getSelect());

        /** @see https://github.com/yiisoft/yii2/issues/17384 */
        $query = new Query($this->getConnection());
        $query->select('DISTINCT ON(tour_dates.date_from) tour_dates.date_from, tour_dates.id');
        $this->assertEquals(
            ['DISTINCT ON(tour_dates.date_from) tour_dates.date_from', 'tour_dates.id' => 'tour_dates.id'],
            $query->getSelect()
        );
    }

    public function testFrom(): void
    {
        $query = new Query($this->getConnection());
        $query->from('user');
        $this->assertEquals(['user'], $query->getFrom());
    }

    public function testFromTableIsArrayWithExpression(): void
    {
        $query = new Query($this->getConnection());
        $tables = new Expression('(SELECT id,name FROM user) u');
        $query->from($tables);
        $this->assertInstanceOf(Expression::class, $query->getFrom()[0]);
    }

    use GetTablesAliasTestTrait;

    protected function createQuery(): Query
    {
        return new Query($this->getConnection());
    }

    public function testWhere(): void
    {
        $query = new Query($this->getConnection());
        $query->where('id = :id', [':id' => 1]);
        $this->assertEquals('id = :id', $query->getWhere());
        $this->assertEquals([':id' => 1], $query->getParams());

        $query->andWhere('name = :name', [':name' => 'something']);
        $this->assertEquals(['and', 'id = :id', 'name = :name'], $query->getWhere());
        $this->assertEquals([':id' => 1, ':name' => 'something'], $query->getParams());

        $query->orWhere('age = :age', [':age' => '30']);
        $this->assertEquals(['or', ['and', 'id = :id', 'name = :name'], 'age = :age'], $query->getWhere());
        $this->assertEquals([':id' => 1, ':name' => 'something', ':age' => '30'], $query->getParams());
    }

    public function testFilterWhereWithHashFormat(): void
    {
        $query = new Query($this->getConnection());
        $query->filterWhere([
            'id'         => 0,
            'title'      => '   ',
            'author_ids' => [],
        ]);
        $this->assertEquals(['id' => 0], $query->getWhere());

        $query->andFilterWhere(['status' => null]);
        $this->assertEquals(['id' => 0], $query->getWhere());

        $query->orFilterWhere(['name' => '']);
        $this->assertEquals(['id' => 0], $query->getWhere());
    }

    public function testFilterWhereWithOperatorFormat(): void
    {
        $query = new Query($this->getConnection());
        $condition = ['like', 'name', 'Alex'];
        $query->filterWhere($condition);
        $this->assertEquals($condition, $query->getWhere());

        $query->andFilterWhere(['between', 'id', null, null]);
        $this->assertEquals($condition, $query->getWhere());

        $query->orFilterWhere(['not between', 'id', null, null]);
        $this->assertEquals($condition, $query->getWhere());

        $query->andFilterWhere(['in', 'id', []]);
        $this->assertEquals($condition, $query->getWhere());

        $query->andFilterWhere(['not in', 'id', []]);
        $this->assertEquals($condition, $query->getWhere());

        $query->andFilterWhere(['like', 'id', '']);
        $this->assertEquals($condition, $query->getWhere());

        $query->andFilterWhere(['or like', 'id', '']);
        $this->assertEquals($condition, $query->getWhere());

        $query->andFilterWhere(['not like', 'id', '   ']);
        $this->assertEquals($condition, $query->getWhere());

        $query->andFilterWhere(['or not like', 'id', null]);
        $this->assertEquals($condition, $query->getWhere());

        $query->andFilterWhere(['or', ['eq', 'id', null], ['eq', 'id', []]]);
        $this->assertEquals($condition, $query->getWhere());
    }

    public function testFilterHavingWithHashFormat(): void
    {
        $query = new Query($this->getConnection());
        $query->filterHaving([
            'id'         => 0,
            'title'      => '   ',
            'author_ids' => [],
        ]);
        $this->assertEquals(['id' => 0], $query->getHaving());

        $query->andFilterHaving(['status' => null]);
        $this->assertEquals(['id' => 0], $query->getHaving());

        $query->orFilterHaving(['name' => '']);
        $this->assertEquals(['id' => 0], $query->getHaving());
    }

    public function testFilterHavingWithOperatorFormat(): void
    {
        $query = new Query($this->getConnection());
        $condition = ['like', 'name', 'Alex'];
        $query->filterHaving($condition);
        $this->assertEquals($condition, $query->getHaving());

        $query->andFilterHaving(['between', 'id', null, null]);
        $this->assertEquals($condition, $query->getHaving());

        $query->orFilterHaving(['not between', 'id', null, null]);
        $this->assertEquals($condition, $query->getHaving());

        $query->andFilterHaving(['in', 'id', []]);
        $this->assertEquals($condition, $query->getHaving());

        $query->andFilterHaving(['not in', 'id', []]);
        $this->assertEquals($condition, $query->getHaving());

        $query->andFilterHaving(['like', 'id', '']);
        $this->assertEquals($condition, $query->getHaving());

        $query->andFilterHaving(['or like', 'id', '']);
        $this->assertEquals($condition, $query->getHaving());

        $query->andFilterHaving(['not like', 'id', '   ']);
        $this->assertEquals($condition, $query->getHaving());

        $query->andFilterHaving(['or not like', 'id', null]);
        $this->assertEquals($condition, $query->getHaving());

        $query->andFilterHaving(['or', ['eq', 'id', null], ['eq', 'id', []]]);
        $this->assertEquals($condition, $query->getHaving());
    }

    public function testFilterRecursively(): void
    {
        $query = new Query($this->getConnection());
        $query->filterWhere(['and', ['like', 'name', ''], ['like', 'title', ''], ['id' => 1], ['not', ['like', 'name', '']]]);
        $this->assertEquals(['and', ['id' => 1]], $query->getWhere());
    }

    public function testGroup(): void
    {
        $query = new Query($this->getConnection());
        $query->groupBy('team');
        $this->assertEquals(['team'], $query->getGroupBy());

        $query->addGroupBy('company');
        $this->assertEquals(['team', 'company'], $query->getGroupBy());

        $query->addGroupBy('age');
        $this->assertEquals(['team', 'company', 'age'], $query->getGroupBy());
    }

    public function testHaving(): void
    {
        $query = new Query($this->getConnection());
        $query->having('id = :id', [':id' => 1]);
        $this->assertEquals('id = :id', $query->getHaving());
        $this->assertEquals([':id' => 1], $query->getParams());

        $query->andHaving('name = :name', [':name' => 'something']);
        $this->assertEquals(['and', 'id = :id', 'name = :name'], $query->getHaving());
        $this->assertEquals([':id' => 1, ':name' => 'something'], $query->getParams());

        $query->orHaving('age = :age', [':age' => '30']);
        $this->assertEquals(['or', ['and', 'id = :id', 'name = :name'], 'age = :age'], $query->getHaving());
        $this->assertEquals([':id' => 1, ':name' => 'something', ':age' => '30'], $query->getParams());
    }

    public function testOrder(): void
    {
        $query = new Query($this->getConnection());
        $query->orderBy('team');
        $this->assertEquals(['team' => SORT_ASC], $query->getOrderBy());

        $query->addOrderBy('company');
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_ASC], $query->getOrderBy());

        $query->addOrderBy('age');
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_ASC], $query->getOrderBy());

        $query->addOrderBy(['age' => SORT_DESC]);
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_DESC], $query->getOrderBy());

        $query->addOrderBy('age ASC, company DESC');
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_DESC, 'age' => SORT_ASC], $query->getOrderBy());

        $expression = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');
        $query->orderBy($expression);
        $this->assertEquals([$expression], $query->getOrderBy());

        $expression = new Expression('SUBSTR(name, 3, 4) DESC, x ASC');
        $query->addOrderBy($expression);
        $this->assertEquals([$expression, $expression], $query->getOrderBy());
    }

    public function testLimitOffset(): void
    {
        $query = new Query($this->getConnection());
        $query->limit(10)->offset(5);
        $this->assertEquals(10, $query->getLimit());
        $this->assertEquals(5, $query->getOffset());
    }

    public function testLimitOffsetWithExpression(): void
    {
        $query = (new Query($this->getConnection()))->from('customer')->select('id')->orderBy('id');
        $query
            ->limit(new Expression('1 + 1'))
            ->offset(new Expression('1 + 0'));

        $result = $query->column($this->getConnection());

        $this->assertCount(2, $result);
        $this->assertContains(2, $result);
        $this->assertContains(3, $result);
        $this->assertNotContains(1, $result);
    }

    public function testUnion(): void
    {
        $connection = $this->getConnection();
        $query = new Query($this->getConnection());
        $query->select(['id', 'name'])
            ->from('item')
            ->limit(2)
            ->union(
                (new Query($this->getConnection()))
                    ->select(['id', 'name'])
                    ->from(['category'])
                    ->limit(2)
            );
        $result = $query->all($connection);
        $this->assertNotEmpty($result);
        $this->assertCount(4, $result);
    }

    public function testOne(): void
    {
        $db = $this->getConnection();

        $result = (new Query($this->getConnection()))->from('customer')->where(['status' => 2])->one($db);
        $this->assertEquals('user3', $result['name']);

        $result = (new Query($this->getConnection()))->from('customer')->where(['status' => 3])->one($db);
        $this->assertFalse($result);
    }

    public function testExists(): void
    {
        $db = $this->getConnection();

        $result = (new Query($this->getConnection()))->from('customer')->where(['status' => 2])->exists($db);
        $this->assertTrue($result);

        $result = (new Query($this->getConnection()))->from('customer')->where(['status' => 3])->exists($db);
        $this->assertFalse($result);
    }

    public function testColumn(): void
    {
        $db = $this->getConnection();
        $result = (new Query($this->getConnection()))->select('name')->from('customer')->orderBy(['id' => SORT_DESC])->column($db);
        $this->assertEquals(['user3', 'user2', 'user1'], $result);

        // https://github.com/yiisoft/yii2/issues/7515
        $result = (new Query($this->getConnection()))->from('customer')
            ->select('name')
            ->orderBy(['id' => SORT_DESC])
            ->indexBy('id')
            ->column($db);
        $this->assertEquals([3 => 'user3', 2 => 'user2', 1 => 'user1'], $result);

        // https://github.com/yiisoft/yii2/issues/12649
        $result = (new Query($this->getConnection()))->from('customer')
            ->select(['name', 'id'])
            ->orderBy(['id' => SORT_DESC])
            ->indexBy(function ($row) {
                return $row['id'] * 2;
            })
            ->column($db);
        $this->assertEquals([6 => 'user3', 4 => 'user2', 2 => 'user1'], $result);

        $result = (new Query($this->getConnection()))->from('customer')
            ->select(['name'])
            ->indexBy('name')
            ->orderBy(['id' => SORT_DESC])
            ->column($db);
        $this->assertEquals(['user3' => 'user3', 'user2' => 'user2', 'user1' => 'user1'], $result);
    }

    /**
     * Ensure no ambiguous column error occurs on indexBy with JOIN.
     *
     * @see https://github.com/yiisoft/yii2/issues/13859
     */
    public function testAmbiguousColumnIndexBy(): void
    {
        switch ($this->driverName) {
            case 'pgsql':
            case 'sqlite':
                $selectExpression = "(customer.name || ' in ' || p.description) AS name";
                break;
            case 'cubird':
            case 'mysql':
                $selectExpression = "concat(customer.name,' in ', p.description) name";
                break;
            default:
                $this->markTestIncomplete('CONCAT syntax for this DBMS is not added to the test yet.');
        }

        $db = $this->getConnection();

        $result = (new Query($this->getConnection()))->select([$selectExpression])->from('customer')
            ->innerJoin('profile p', '{{customer}}.[[profile_id]] = {{p}}.[[id]]')
            ->indexBy('id')->column($db);
        $this->assertEquals([
            1 => 'user1 in profile customer 1',
            3 => 'user3 in profile customer 3',
        ], $result);
    }

    public function testCount(): void
    {
        $db = $this->getConnection();

        $count = (new Query($this->getConnection()))->from('customer')->count('*', $db);
        $this->assertEquals(3, $count);

        $count = (new Query($this->getConnection()))->from('customer')->where(['status' => 2])->count('*', $db);
        $this->assertEquals(1, $count);

        $count = (new Query($this->getConnection()))->select('[[status]], COUNT([[id]])')
            ->from('customer')->groupBy('status')->count('*');

        $this->assertEquals(2, $count);

        // testing that orderBy() should be ignored here as it does not affect the count anyway.
        $count = (new Query($this->getConnection()))->from('customer')->orderBy('status')->count('*', $db);
        $this->assertEquals(3, $count);

        $count = (new Query($this->getConnection()))->from('customer')->orderBy('id')->limit(1)->count('*', $db);
        $this->assertEquals(3, $count);
    }

    /**
     * @depends testFilterWhereWithHashFormat
     * @depends testFilterWhereWithOperatorFormat
     */
    public function testAndFilterCompare(): void
    {
        $query = new Query($this->getConnection());

        $result = $query->andFilterCompare('name', null);
        $this->assertInstanceOf('Yiisoft\Db\Querys\Query', $result);
        $this->assertNull($query->getWhere());

        $query->andFilterCompare('name', '');
        $this->assertNull($query->getWhere());

        $query->andFilterCompare('name', 'John Doe');
        $condition = ['=', 'name', 'John Doe'];
        $this->assertEquals($condition, $query->getWhere());

        $condition = ['and', $condition, ['like', 'name', 'Doe']];
        $query->andFilterCompare('name', 'Doe', 'like');
        $this->assertEquals($condition, $query->getWhere());

        $condition[] = ['>', 'rating', '9'];
        $query->andFilterCompare('rating', '>9');
        $this->assertEquals($condition, $query->getWhere());

        $condition[] = ['<=', 'value', '100'];
        $query->andFilterCompare('value', '<=100');
        $this->assertEquals($condition, $query->getWhere());
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/8068
     *
     * @depends testCount
     */
    public function testCountHavingWithoutGroupBy(): void
    {
        if (!\in_array($this->driverName, ['mysql'])) {
            $this->markTestSkipped("{$this->driverName} does not support having without group by.");
        }

        $db = $this->getConnection();

        $count = (new Query($this->getConnection()))->from('customer')->having(['status' => 2])->count('*', $db);
        $this->assertEquals(1, $count);
    }

    public function testEmulateExecution(): void
    {
        $db = $this->getConnection();

        $this->assertGreaterThan(0, (new Query($this->getConnection()))->from('customer')->count('*', $db));

        $rows = (new Query($this->getConnection()))
            ->from('customer')
            ->emulateExecution()
            ->all($db);
        $this->assertSame([], $rows);

        $row = (new Query($this->getConnection()))
            ->from('customer')
            ->emulateExecution()
            ->one($db);
        $this->assertFalse($row);

        $exists = (new Query($this->getConnection()))
            ->from('customer')
            ->emulateExecution()
            ->exists($db);
        $this->assertFalse($exists);

        $count = (new Query($this->getConnection()))
            ->from('customer')
            ->emulateExecution()
            ->count('*', $db);
        $this->assertSame(0, $count);

        $sum = (new Query($this->getConnection()))
            ->from('customer')
            ->emulateExecution()
            ->sum('id', $db);
        $this->assertSame(0, $sum);

        $sum = (new Query($this->getConnection()))
            ->from('customer')
            ->emulateExecution()
            ->average('id', $db);
        $this->assertSame(0, $sum);

        $max = (new Query($this->getConnection()))
            ->from('customer')
            ->emulateExecution()
            ->max('id', $db);
        $this->assertNull($max);

        $min = (new Query($this->getConnection()))
            ->from('customer')
            ->emulateExecution()
            ->min('id', $db);
        $this->assertNull($min);

        $scalar = (new Query($this->getConnection()))
            ->select(['id'])
            ->from('customer')
            ->emulateExecution()
            ->scalar($db);
        $this->assertNull($scalar);

        $column = (new Query($this->getConnection()))
            ->select(['id'])
            ->from('customer')
            ->emulateExecution()
            ->column($db);
        $this->assertSame([], $column);
    }

    /**
     * @param Connection $db
     * @param string $tableName
     * @param string $columnName
     * @param array $condition
     * @param string $operator
     *
     * @return int
     */
    protected function countLikeQuery(Connection $db, $tableName, $columnName, array $condition, $operator = 'or'): int
    {
        $whereCondition = [$operator];

        foreach ($condition as $value) {
            $whereCondition[] = ['like', $columnName, $value];
        }

        $result = (new Query($this->getConnection()))
            ->from($tableName)
            ->where($whereCondition)
            ->count('*', $db);

        if (is_numeric($result)) {
            $result = (int) $result;
        }

        return $result;
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13745
     */
    public function testMultipleLikeConditions(): void
    {
        $db = $this->getConnection();
        $tableName = 'like_test';
        $columnName = 'col';

        if ($db->getSchema()->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable($tableName, [
            $columnName => $db->getSchema()->createColumnSchemaBuilder(Schema::TYPE_STRING, 64),
        ])->execute();
        $db->createCommand()->batchInsert($tableName, ['col'], [
            ['test0'],
            ['test\1'],
            ['test\2'],
            ['foo%'],
            ['%bar'],
            ['%baz%'],
        ])->execute();

        // Basic tests
        $this->assertSame(1, $this->countLikeQuery($db, $tableName, $columnName, ['test0']));
        $this->assertSame(2, $this->countLikeQuery($db, $tableName, $columnName, ['test\\']));
        $this->assertSame(0, $this->countLikeQuery($db, $tableName, $columnName, ['test%']));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, ['%']));

        // Multiple condition tests
        $this->assertSame(2, $this->countLikeQuery($db, $tableName, $columnName, [
            'test0',
            'test\1',
        ]));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, [
            'test0',
            'test\1',
            'test\2',
        ]));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, [
            'foo',
            '%ba',
        ]));
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/15355
     */
    public function testExpressionInFrom(): void
    {
        $db = $this->getConnection();

        $query = (new Query($this->getConnection()))
            ->from(new Expression('(SELECT id, name, email, address, status FROM customer) c'))
            ->where(['status' => 2]);

        $result = $query->one($db);
        $this->assertEquals('user3', $result['name']);
    }

    public function testQueryCache()
    {
        $db = $this->getConnection(true, true, true);

        $db->setEnableQueryCache(true);
        $db->setQueryCache($this->cache);

        $query = (new Query($db))
            ->select(['name'])
            ->from('customer');

        $update = $db->createCommand('UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id');

        $this->assertEquals('user1', $query->where(['id' => 1])->scalar($db), 'Asserting initial value');

        // No cache
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();
        $this->assertEquals(
            'user11',
            $query->where(['id' => 1])->scalar($db),
            'Query reflects DB changes when caching is disabled'
        );

        // Connection cache
        $db->cache(function (Connection $db) use ($query, $update) {
            $this->assertEquals(
                'user2',
                $query->where(['id' => 2])->scalar($db),
                'Asserting initial value for user #2'
            );

            $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();
            $this->assertEquals(
                'user2',
                $query->where(['id' => 2])->scalar($db),
                'Query does NOT reflect DB changes when wrapped in connection caching'
            );

            $db->noCache(function () use ($query, $db) {
                $this->assertEquals(
                    'user22',
                    $query->where(['id' => 2])->scalar($db),
                    'Query reflects DB changes when wrapped in connection caching and noCache simultaneously'
                );
            });

            $this->assertEquals('user2', $query->where(['id' => 2])->scalar($db), 'Cache does not get changes after getting newer data from DB in noCache block.');
        }, 10);

        $db->setEnableQueryCache(false);

        $db->cache(function ($db) use ($query, $update) {
            $this->assertEquals('user22', $query->where(['id' => 2])->scalar($db), 'When cache is disabled for the whole connection, Query inside cache block does not get cached');
            $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();
            $this->assertEquals('user2', $query->where(['id' => 2])->scalar($db));
        }, 10);

        $db->setEnableQueryCache(true);
        $query->cache();

        $this->assertEquals('user11', $query->where(['id' => 1])->scalar($db));
        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();
        $this->assertEquals('user11', $query->where(['id' => 1])->scalar($db), 'When both Connection and Query have cache enabled, we get cached value');
        $this->assertEquals('user1', $query->noCache()->where(['id' => 1])->scalar($db), 'When Query has disabled cache, we get actual data');

        $db->cache(function (Connection $db) use ($query, $update) {
            $this->assertEquals('user1', $query->noCache()->where(['id' => 1])->scalar($db));
            $this->assertEquals('user11', $query->cache()->where(['id' => 1])->scalar($db));
        }, 10);
    }
}
