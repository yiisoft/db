<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Data\Sort;

final class SortTest extends TestCase
{
    public function testGetOrders(): void
    {
        $sort = new Sort();

        $sort->attributes(
            [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                ]
            ]
        )->params(['sort' => 'age,-name'])->enableMultiSort(true);

        $orders = $sort->getOrders();

        $this->assertCount(3, $orders);
        $this->assertEquals(SORT_ASC, $orders['age']);
        $this->assertEquals(SORT_DESC, $orders['first_name']);
        $this->assertEquals(SORT_DESC, $orders['last_name']);

        $sort->enableMultiSort(false);

        $orders = $sort->getOrders(true);

        $this->assertCount(1, $orders);
        $this->assertEquals(SORT_ASC, $orders['age']);
    }

    /**
     * @depends testGetOrders
     */
    public function testGetAttributeOrders()
    {
        $sort = new Sort();

        $sort->attributes(
            [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                ],
            ],
        )->params(['sort' => 'age,-name'])->enableMultiSort(true);

        $orders = $sort->getAttributeOrders();
        $this->assertCount(2, $orders);
        $this->assertEquals(SORT_ASC, $orders['age']);
        $this->assertEquals(SORT_DESC, $orders['name']);

        $sort->enableMultiSort(false);
        $orders = $sort->getAttributeOrders(true);
        $this->assertCount(1, $orders);
        $this->assertEquals(SORT_ASC, $orders['age']);
    }

    /**
     * @depends testGetAttributeOrders
     */
    public function testGetAttributeOrder()
    {
        $sort = new Sort();

        $sort->attributes(
            [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                ],
            ]
        )->params(['sort' => 'age,-name'])->enableMultiSort(true);

        $this->assertEquals(SORT_ASC, $sort->getAttributeOrder('age'));
        $this->assertEquals(SORT_DESC, $sort->getAttributeOrder('name'));
        $this->assertNull($sort->getAttributeOrder('xyz'));
    }

    public function testAttributeOrders()
    {
        $sort = new Sort();

        $sort->attributes(
            [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                ],
            ]
        )->params(['sort' => 'age,-name'])->enableMultiSort(true);

        $sort->attributeOrders(['age' => SORT_DESC, 'name' => SORT_ASC]);
        $this->assertEquals(['age' => SORT_DESC, 'name' => SORT_ASC], $sort->getAttributeOrders());

        $sort->enableMultiSort(false);
        $sort->attributeOrders(['age' => SORT_DESC, 'name' => SORT_ASC]);
        $this->assertEquals(['age' => SORT_DESC], $sort->getAttributeOrders());

        $sort->attributeOrders(['age' => SORT_DESC, 'name' => SORT_ASC], false);
        $this->assertEquals(['age' => SORT_DESC, 'name' => SORT_ASC], $sort->getAttributeOrders());

        $sort->attributeOrders(['unexistingAttribute' => SORT_ASC]);
        $this->assertEquals([], $sort->getAttributeOrders());

        $sort->attributeOrders(['unexistingAttribute' => SORT_ASC], false);
        $this->assertEquals(['unexistingAttribute' => SORT_ASC], $sort->getAttributeOrders());
    }

    public function testCreateSortParam()
    {
        $sort = new Sort();

        $sort->attributes(
            [
                'age',
                'name' => [
                    'asc' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC],
                    'desc' => ['first_name' => SORT_DESC, 'last_name' => SORT_DESC],
                ],
            ]
        )->params(['sort' => 'age,-name'])->enableMultiSort(true);

        $this->assertEquals('-age,-name', $sort->createSortParam('age'));
        $this->assertEquals('name,age', $sort->createSortParam('name'));
    }

    /**
     * @depends testGetOrders
     *
     * @see https://github.com/yiisoft/yii2/pull/13260
     */
    public function testGetExpressionOrders()
    {
        $sort = new Sort();

        $sort->attributes(
            [
                'name' => [
                    'asc' => '[[last_name]] ASC NULLS FIRST',
                    'desc' => '[[last_name]] DESC NULLS LAST',
                ],
            ]
        );

        $sort->params(['sort' => '-name']);
        $orders = $sort->getOrders();
        $this->assertEquals(1, count($orders));
        $this->assertEquals('[[last_name]] DESC NULLS LAST', $orders[0]);

        $sort->params(['sort' => 'name']);
        $orders = $sort->getOrders(true);
        $this->assertEquals(1, count($orders));
        $this->assertEquals('[[last_name]] ASC NULLS FIRST', $orders[0]);
    }
}
