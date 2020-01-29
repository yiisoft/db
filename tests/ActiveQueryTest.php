<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\Db\Connection;
use Yiisoft\Db\Command;
use Yiisoft\Db\Query;
use Yiisoft\Db\QueryBuilder;
use Yiisoft\Db\Connectors\ConnectionPool;
use Yiisoft\Db\Contracts\ConnectionInterface;
use Yiisoft\ActiveRecord\Tests\Data\ActiveRecord;
use Yiisoft\ActiveRecord\Tests\Data\Category;
use Yiisoft\ActiveRecord\Tests\Data\Customer;
use Yiisoft\ActiveRecord\Tests\Data\Order;
use Yiisoft\ActiveRecord\Tests\Data\Profile;

/**
 * Class ActiveQueryTest the base class for testing ActiveQuery.
 */
abstract class ActiveQueryTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->getConnection();
    }

    public function testOptions()
    {
        $query = new ActiveQuery(Customer::class);
        $query->setOn(['a' => 'b']);
        $query->setJoinWith(['dummy relation']);

        $this->assertEquals($query->modelClass, Customer::class);
        $this->assertEquals($query->on, ['a' => 'b']);
        $this->assertEquals($query->joinWith, ['dummy relation']);
    }

    /**
     * @todo: tests for internal logic of prepare()
     */
    public function testPrepare()
    {
        $query = new ActiveQuery(Customer::class);
        $builder = new QueryBuilder($this->db);

        $result = $query->prepare($builder);

        $this->assertInstanceOf(\Yiisoft\Db\Query::class, $result);
    }

    public function testPopulateEmptyRows()
    {
        $query = new ActiveQuery(Customer::class);

        $rows = [];

        $result = $query->populate([]);

        $this->assertEquals($rows, $result);
    }

    /**
     * @todo: tests for internal logic of populate()
     */
    public function testPopulateFilledRows()
    {
        $query = new ActiveQuery(Customer::class);

        $rows = $query->all();

        $result = $query->populate($rows);

        $this->assertEquals($rows, $result);
    }

    /**
     * @todo: tests for internal logic of one()
     */
    public function testOne()
    {
        $query = new ActiveQuery(Customer::class);

        $result = $query->one();

        $this->assertInstanceOf(Customer::class, $result);
    }

    /**
     * @todo: test internal logic of createCommand()
     */
    public function testCreateCommand()
    {
        $query = new ActiveQuery(Customer::class);

        $result = $query->createCommand();

        $this->assertInstanceOf(Command::class, $result);
    }

    /**
     * @todo: tests for internal logic of queryScalar()
     */
    public function testQueryScalar()
    {
        $query = new ActiveQuery(Customer::class);

        $result = $this->invokeMethod($query, 'queryScalar', ['name']);

        $this->assertEquals('user1', $result);
    }

    /**
     * @todo: tests for internal logic of joinWith()
     */
    public function testJoinWith()
    {
        $query = new ActiveQuery(Customer::class);

        $result = $query->joinWith('profile');

        $this->assertEquals([[['profile'], true, 'LEFT JOIN']], $result->joinWith);
    }

    /**
     * @todo: tests for internal logic of innerJoinWith()
     */
    public function testInnerJoinWith()
    {
        $query = new ActiveQuery(Customer::class);

        $result = $query->innerJoinWith('profile');

        $this->assertEquals([[['profile'], true, 'INNER JOIN']], $result->joinWith);
    }

    /**
     * @todo: tests for the regex inside getQueryTableName
     */
    public function testGetQueryTableNameFromNotSet()
    {
        $query = new ActiveQuery(Customer::class);

        $result = $this->invokeMethod($query, 'getTableNameAndAlias');

        $this->assertEquals(['customer', 'customer'], $result);
    }

    public function testGetQueryTableNameFromSet()
    {
        $query = new ActiveQuery(Customer::class);
        $query->setFrom(['alias' => 'customer']);

        $result = $this->invokeMethod($query, 'getTableNameAndAlias');

        $this->assertEquals(['customer', 'alias'], $result);
    }

    public function testOnCondition()
    {
        $query = new ActiveQuery(Customer::class);

        $on = ['active' => true];
        $params = ['a' => 'b'];

        $result = $query->onCondition($on, $params);

        $this->assertEquals($on, $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testAndOnConditionOnNotSet()
    {
        $query = new ActiveQuery(Customer::class);

        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->andOnCondition($on, $params);

        $this->assertEquals($on, $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testAndOnConditionOnSet()
    {
        $onOld = ['active' => true];

        $query = new ActiveQuery(Customer::class);

        $query->on = $onOld;

        $on = ['active' => true];
        $params = ['a' => 'b'];

        $result = $query->andOnCondition($on, $params);

        $this->assertEquals(['and', $onOld, $on], $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testOrOnConditionOnNotSet()
    {
        $query = new ActiveQuery(Customer::class);

        $on = ['active' => true];
        $params = ['a' => 'b'];
        $result = $query->orOnCondition($on, $params);

        $this->assertEquals($on, $result->on);
        $this->assertEquals($params, $result->params);
    }

    public function testOrOnConditionOnSet()
    {
        $onOld = ['active' => true];

        $query = new ActiveQuery(Customer::class);

        $query->on = $onOld;

        $on = ['active' => true];
        $params = ['a' => 'b'];

        $result = $query->orOnCondition($on, $params);

        $this->assertEquals(['or', $onOld, $on], $result->on);
        $this->assertEquals($params, $result->params);
    }

    /**
     * @todo: tests for internal logic of viaTable()
     */
    public function testViaTable()
    {
        $query = new ActiveQuery(
            Customer::class,
            ['primaryModel' => new Order($this->db)]
        );

        $result = $query->viaTable(Profile::class, ['id' => 'item_id']);

        $this->assertInstanceOf(ActiveQuery::class, $result);
        $this->assertInstanceOf(ActiveQuery::class, $result->via);
    }

    public function testAliasNotSet()
    {
        $query = new ActiveQuery(Customer::class);

        $result = $query->alias('alias');

        $this->assertInstanceOf(ActiveQuery::class, $result);
        $this->assertEquals(['alias' => 'customer'], $result->from);
    }

    public function testAliasYetSet()
    {
        $aliasOld = ['old'];

        $query = new ActiveQuery(Customer::class);

        $query->from = $aliasOld;

        $result = $query->alias('alias');

        $this->assertInstanceOf(ActiveQuery::class, $result);
        $this->assertEquals(['alias' => 'old'], $result->from);
    }

    use GetTablesAliasTestTrait;

    protected function createQuery(ConnectionInterface $db)
    {
        return new Query($this->db);
    }

    public function testGetTableNamesNotFilledFrom()
    {
        $query = new ActiveQuery(Profile::class);

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals([
            '{{' . Profile::tableName() . '}}' => '{{' . Profile::tableName() . '}}',
        ], $tables);
    }

    public function testGetTableNamesWontFillFrom()
    {
        $query = new ActiveQuery(Profile::class);

        $this->assertEquals($query->from, null);

        $query->getTablesUsedInFrom();

        $this->assertEquals($query->from, null);
    }

    /**
     * https://github.com/yiisoft/yii2/issues/5341
     *
     * Issue:     Plan     1 -- * Account * -- * User
     * Our Tests: Category 1 -- * Item    * -- * Order
     */
    public function testDeeplyNestedTableRelationWith()
    {
        /* @var $category Category */
        $categories = (new Category())->find()
            ->with('orders')->indexBy('id')->all();

        $category = $categories[1];

        $this->assertNotNull($category);

        $orders = $category->orders;

        $this->assertEquals(2, count($orders));
        $this->assertInstanceOf(Order::class, $orders[0]);
        $this->assertInstanceOf(Order::class, $orders[1]);

        $ids = [$orders[0]->id, $orders[1]->id];

        sort($ids);

        $this->assertEquals([1, 3], $ids);

        $category = $categories[2];

        $this->assertNotNull($category);

        $orders = $category->orders;

        $this->assertEquals(1, count($orders));
        $this->assertInstanceOf(Order::class, $orders[0]);
        $this->assertEquals(2, $orders[0]->id);
    }
}
