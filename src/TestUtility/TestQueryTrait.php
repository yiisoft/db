<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;

trait TestQueryTrait
{
    use GetTablesAliasTestTrait;

    public function testSelect(): void
    {
        $db = $this->getConnection();

        /* default */
        $query = new Query($db);

        $query->select('*');

        $this->assertEquals(['*' => '*'], $query->getSelect());
        $this->assertNull($query->getDistinct());
        $this->assertNull($query->getSelectOption());

        $query = new Query($db);

        $query->select('id, name', 'something')->distinct(true);

        $this->assertEquals(['id' => 'id', 'name' => 'name'], $query->getSelect());
        $this->assertTrue($query->getDistinct());
        $this->assertEquals('something', $query->getSelectOption());

        $query = new Query($db);

        $query->addSelect('email');

        $this->assertEquals(['email' => 'email'], $query->getSelect());

        $query = new Query($db);

        $query->select('id, name');
        $query->addSelect('email');

        $this->assertEquals(['id' => 'id', 'name' => 'name', 'email' => 'email'], $query->getSelect());

        $query = new Query($db);

        $query->select('name, lastname');
        $query->addSelect('name');

        $this->assertEquals(['name' => 'name', 'lastname' => 'lastname'], $query->getSelect());

        $query = new Query($db);

        $query->addSelect(['*', 'abc']);
        $query->addSelect(['*', 'bca']);

        $this->assertEquals(['*' => '*', 'abc' => 'abc', 'bca' => 'bca'], $query->getSelect());

        $query = new Query($db);

        $query->addSelect(['field1 as a', 'field 1 as b']);

        $this->assertEquals(['a' => 'field1', 'b' => 'field 1'], $query->getSelect());

        $query = new Query($db);

        $query->addSelect(['field1 a', 'field 1 b']);

        $this->assertEquals(['a' => 'field1', 'b' => 'field 1'], $query->getSelect());

        $query = new Query($db);

        $query->select(['name' => 'firstname', 'lastname']);
        $query->addSelect(['firstname', 'surname' => 'lastname']);
        $query->addSelect(['firstname', 'lastname']);

        $this->assertEquals(
            ['name' => 'firstname', 'lastname' => 'lastname', 'firstname' => 'firstname', 'surname' => 'lastname'],
            $query->getSelect()
        );

        $query = new Query($db);

        $query->select('name, name, name as X, name as X');

        $this->assertEquals(['name' => 'name', 'X' => 'name'], $query->getSelect());

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
        $query = (new Query($db))
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

        /**
         * {@see https://github.com/yiisoft/yii2/issues/15731}
         */
        $selectedCols = [
            'total_sum' => 'SUM(f.amount)',
            'in_sum' => 'SUM(IF(f.type = :type_in, f.amount, 0))',
            'out_sum' => 'SUM(IF(f.type = :type_out, f.amount, 0))',
        ];

        $query = (new Query($db))->select($selectedCols)->addParams([
            ':type_in' => 'in',
            ':type_out' => 'out',
            ':type_partner' => 'partner',
        ]);

        $this->assertSame($selectedCols, $query->getSelect());

        $query->select($selectedCols);

        $this->assertSame($selectedCols, $query->getSelect());

        /**
         * {@see https://github.com/yiisoft/yii2/issues/17384}
         */
        $query = new Query($db);

        $query->select('DISTINCT ON(tour_dates.date_from) tour_dates.date_from, tour_dates.id');

        $this->assertEquals(
            ['DISTINCT ON(tour_dates.date_from) tour_dates.date_from', 'tour_dates.id' => 'tour_dates.id'],
            $query->getSelect()
        );
    }

    public function testFrom(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

        $query->from('user');

        $this->assertEquals(['user'], $query->getFrom());
    }

    public function testFromTableIsArrayWithExpression(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

        $tables = new Expression('(SELECT id,name FROM user) u');

        $query->from($tables);

        $this->assertInstanceOf(Expression::class, $query->getFrom()[0]);
    }

    protected function createQuery(): Query
    {
        return new Query($this->getConnection());
    }

    public function testWhere(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

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
        $db = $this->getConnection();

        $query = new Query($db);

        $query->filterWhere([
            'id' => 0,
            'title' => '   ',
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
        $db = $this->getConnection();

        $query = new Query($db);

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
        $db = $this->getConnection();

        $query = new Query($db);

        $query->filterHaving([
            'id' => 0,
            'title' => '   ',
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
        $db = $this->getConnection();

        $query = new Query($db);

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
        $db = $this->getConnection();

        $query = new Query($db);

        $query->filterWhere(
            ['and', ['like', 'name', ''],
                ['like', 'title', ''],
                ['id' => 1],
                ['not',
                    ['like', 'name', ''], ], ]
        );

        $this->assertEquals(['and', ['id' => 1]], $query->getWhere());
    }

    public function testGroup(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

        $query->groupBy('team');

        $this->assertEquals(['team'], $query->getGroupBy());

        $query->addGroupBy('company');

        $this->assertEquals(['team', 'company'], $query->getGroupBy());

        $query->addGroupBy('age');

        $this->assertEquals(['team', 'company', 'age'], $query->getGroupBy());
    }

    public function testHaving(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

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
        $db = $this->getConnection();

        $query = new Query($db);

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
        $db = $this->getConnection();

        $query = new Query($db);

        $query->limit(10)->offset(5);

        $this->assertEquals(10, $query->getLimit());
        $this->assertEquals(5, $query->getOffset());
    }

    public function testLimitOffsetWithExpression(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from('customer')->select('id')->orderBy('id');

        $query
            ->limit(new Expression('1 + 1'))
            ->offset(new Expression('1 + 0'));

        $result = $query->column();

        $this->assertCount(2, $result);

        if ($db->getDriverName() !== 'sqlsrv' && $db->getDriverName() !== 'oci') {
            $this->assertContains(2, $result);
            $this->assertContains(3, $result);
        } else {
            $this->assertContains('2', $result);
            $this->assertContains('3', $result);
        }

        $this->assertNotContains(1, $result);
    }

    public function testOne(): void
    {
        $db = $this->getConnection(true);

        $result = (new Query($db))->from('customer')->where(['status' => 2])->one();

        $this->assertEquals('user3', $result['name']);

        $result = (new Query($db))->from('customer')->where(['status' => 3])->one();

        $this->assertFalse($result);
    }

    public function testExists(): void
    {
        $db = $this->getConnection();

        $result = (new Query($db))->from('customer')->where(['status' => 2])->exists();

        $this->assertTrue($result);

        $result = (new Query($db))->from('customer')->where(['status' => 3])->exists();

        $this->assertFalse($result);
    }

    public function testColumn(): void
    {
        $db = $this->getConnection();

        $result = (new Query($db))
            ->select('name')
            ->from('customer')
            ->orderBy(['id' => SORT_DESC])
            ->column();

        $this->assertEquals(['user3', 'user2', 'user1'], $result);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/7515}
         */
        $result = (new Query($db))->from('customer')
            ->select('name')
            ->orderBy(['id' => SORT_DESC])
            ->indexBy('id')
            ->column();

        $this->assertEquals([3 => 'user3', 2 => 'user2', 1 => 'user1'], $result);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/12649}
         */
        $result = (new Query($db))->from('customer')
            ->select(['name', 'id'])
            ->orderBy(['id' => SORT_DESC])
            ->indexBy(function ($row) {
                return $row['id'] * 2;
            })
            ->column();

        $this->assertEquals([6 => 'user3', 4 => 'user2', 2 => 'user1'], $result);

        $result = (new Query($db))->from('customer')
            ->select(['name'])
            ->indexBy('name')
            ->orderBy(['id' => SORT_DESC])
            ->column($db);

        $this->assertEquals(['user3' => 'user3', 'user2' => 'user2', 'user1' => 'user1'], $result);
    }

    public function testCount(): void
    {
        $db = $this->getConnection();

        $count = (new Query($db))->from('customer')->count('*');

        $this->assertEquals(3, $count);

        $count = (new Query($db))->from('customer')->where(['status' => 2])->count('*');

        $this->assertEquals(1, $count);

        $count = (new Query($db))
            ->select('[[status]], COUNT([[id]]) cnt')
            ->from('customer')
            ->groupBy('status')
            ->count('*');

        $this->assertEquals(2, $count);

        /* testing that orderBy() should be ignored here as it does not affect the count anyway. */
        $count = (new Query($db))->from('customer')->orderBy('status')->count('*');

        $this->assertEquals(3, $count);

        $count = (new Query($db))->from('customer')->orderBy('id')->limit(1)->count('*');

        $this->assertEquals(3, $count);
    }

    /**
     * @depends testFilterWhereWithHashFormat
     * @depends testFilterWhereWithOperatorFormat
     */
    public function testAndFilterCompare(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

        $result = $query->andFilterCompare('name', null);

        $this->assertInstanceOf(Query::class, $result);
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

    public function testEmulateExecution(): void
    {
        $db = $this->getConnection();

        $this->assertGreaterThan(0, (new Query($db))->from('customer')->count('*'));

        $rows = (new Query($db))->from('customer')->emulateExecution()->all();

        $this->assertSame([], $rows);

        $row = (new Query($db))->from('customer')->emulateExecution()->one();

        $this->assertFalse($row);

        $exists = (new Query($db))->from('customer')->emulateExecution()->exists($db);

        $this->assertFalse($exists);

        $count = (new Query($db))->from('customer')->emulateExecution()->count('*');

        $this->assertSame(0, $count);

        $sum = (new Query($db))->from('customer')->emulateExecution()->sum('id');

        $this->assertSame(0, $sum);

        $sum = (new Query($db))->from('customer')->emulateExecution()->average('id');

        $this->assertSame(0, $sum);

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
     * @param Connection $db
     * @param string $tableName
     * @param string $columnName
     * @param array $condition
     * @param string $operator
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return int
     */
    protected function countLikeQuery(
        ConnectionInterface $db,
        string $tableName,
        string $columnName,
        array $condition,
        string $operator = 'or'
    ): int {
        $db = $this->getConnection();

        $whereCondition = [$operator];

        foreach ($condition as $value) {
            $whereCondition[] = ['like', $columnName, $value];
        }

        $result = (new Query($db))->from($tableName)->where($whereCondition)->count('*');

        if (is_numeric($result)) {
            $result = (int) $result;
        }

        return $result;
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/13745}
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

        /* Basic tests */
        $this->assertSame(1, $this->countLikeQuery($db, $tableName, $columnName, ['test0']));
        $this->assertSame(2, $this->countLikeQuery($db, $tableName, $columnName, ['test\\']));
        $this->assertSame(0, $this->countLikeQuery($db, $tableName, $columnName, ['test%']));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, ['%']));

        /* Multiple condition tests */
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
     * {@see https://github.com/yiisoft/yii2/issues/15355}
     */
    public function testExpressionInFrom(): void
    {
        $db = $this->getConnection(true);

        $query = (new Query($db))
            ->from(
                new Expression(
                    '(SELECT [[id]], [[name]], [[email]], [[address]], [[status]] FROM {{customer}}) c'
                )
            )
            ->where(['status' => 2]);

        $result = $query->one();

        $this->assertEquals('user3', $result['name']);
    }

    public function testQueryCache()
    {
        $db = $this->getConnection();

        $this->queryCache->setEnable(true);

        $query = (new Query($db))
            ->select(['name'])
            ->from('customer');

        $update = $db->createCommand('UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id');

        $this->assertEquals('user1', $query->where(['id' => 1])->scalar(), 'Asserting initial value');

        /* No cache */
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();

        $this->assertEquals(
            'user11',
            $query->where(['id' => 1])->scalar(),
            'Query reflects DB changes when caching is disabled'
        );

        /* Connection cache */
        $db->cache(function (ConnectionInterface $db) use ($query, $update) {
            $this->assertEquals(
                'user2',
                $query->where(['id' => 2])->scalar(),
                'Asserting initial value for user #2'
            );

            $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();

            $this->assertEquals(
                'user2',
                $query->where(['id' => 2])->scalar(),
                'Query does NOT reflect DB changes when wrapped in connection caching'
            );

            $db->noCache(function () use ($query) {
                $this->assertEquals(
                    'user22',
                    $query->where(['id' => 2])->scalar(),
                    'Query reflects DB changes when wrapped in connection caching and noCache simultaneously'
                );
            });

            $this->assertEquals(
                'user2',
                $query->where(['id' => 2])->scalar(),
                'Cache does not get changes after getting newer data from DB in noCache block.'
            );
        }, 10);

        $this->queryCache->setEnable(false);

        $db->cache(function () use ($query, $update) {
            $this->assertEquals(
                'user22',
                $query->where(['id' => 2])->scalar(),
                'When cache is disabled for the whole connection, Query inside cache block does not get cached'
            );

            $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();

            $this->assertEquals('user2', $query->where(['id' => 2])->scalar());
        }, 10);

        $this->queryCache->setEnable(true);

        $query->cache();

        $this->assertEquals('user11', $query->where(['id' => 1])->scalar());

        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();

        $this->assertEquals(
            'user11',
            $query->where(['id' => 1])->scalar(),
            'When both Connection and Query have cache enabled, we get cached value'
        );
        $this->assertEquals(
            'user1',
            $query->noCache()->where(['id' => 1])->scalar(),
            'When Query has disabled cache, we get actual data'
        );

        $db->cache(function () use ($query) {
            $this->assertEquals('user1', $query->noCache()->where(['id' => 1])->scalar());
            $this->assertEquals('user11', $query->cache()->where(['id' => 1])->scalar());
        }, 10);
    }

    /**
     * checks that all needed properties copied from source to new query
     */
    public function testQueryCreation(): void
    {
        $db = $this->getConnection();

        $where = 'id > :min_user_id';
        $limit = 50;
        $offset = 2;
        $orderBy = ['name' => SORT_ASC];
        $indexBy = 'id';
        $select = ['id' => 'id', 'name' => 'name', 'articles_count' => 'count(*)'];
        $selectOption = 'SQL_NO_CACHE';
        $from = 'recent_users';
        $groupBy = 'id';
        $having = ['>', 'articles_count', 0];
        $params = [':min_user_id' => 100];

        [$joinType, $joinTable, $joinOn] = $join = ['INNER', 'articles', 'articles.author_id=users.id'];

        $unionQuery = (new Query($db))
            ->select('id, name, 1000 as articles_count')
            ->from('admins');

        $withQuery = (new Query($db))
            ->select('id, name')
            ->from('users')
            ->where('DATE(registered_at) > "2020-01-01"');

        /** build target query */
        $sourceQuery = (new Query($db))
            ->where($where)
            ->limit($limit)
            ->offset($offset)
            ->orderBy($orderBy)
            ->indexBy($indexBy)
            ->select($select, $selectOption)
            ->distinct()
            ->from($from)
            ->groupBy($groupBy)
            ->having($having)
            ->addParams($params)
            ->join($joinType, $joinTable, $joinOn)
            ->union($unionQuery)
            ->withQuery($withQuery, $from);

        $newQuery = Query::create($db, $sourceQuery);

        $this->assertEquals($where, $newQuery->getWhere());
        $this->assertEquals($limit, $newQuery->getLimit());
        $this->assertEquals($offset, $newQuery->getOffset());
        $this->assertEquals($orderBy, $newQuery->getOrderBy());
        $this->assertEquals($indexBy, $newQuery->getIndexBy());
        $this->assertEquals($select, $newQuery->getSelect());
        $this->assertEquals($selectOption, $newQuery->getSelectOption());
        $this->assertTrue($newQuery->getDistinct());
        $this->assertEquals([$from], $newQuery->getFrom());
        $this->assertEquals([$groupBy], $newQuery->getGroupBy());
        $this->assertEquals($having, $newQuery->getHaving());
        $this->assertEquals($params, $newQuery->getParams());
        $this->assertEquals([$join], $newQuery->getJoin());
        $this->assertEquals([['query' => $unionQuery, 'all' => false]], $newQuery->getUnion());
        $this->assertEquals(
            [['query' => $withQuery, 'alias' => $from, 'recursive' => false]],
            $newQuery->getWithQueries()
        );
    }
}
