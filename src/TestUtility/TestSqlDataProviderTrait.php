<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

use Yiisoft\Db\Data\Sort;
use Yiisoft\Db\Data\SqlDataProvider;

trait TestSqlDataProviderTrait
{
    public function testGetModels(): void
    {
        $dataProvider = new SqlDataProvider($this->getConnection(), 'SELECT * FROM {{customer}}');
        $this->assertCount(3, $dataProvider->getModels());
    }

    public function testGetKeys(): void
    {
        $dataProvider = new SqlDataProvider($this->getConnection(), 'SELECT * FROM {{customer}}');
        $this->assertEquals([0, 1, 2], $dataProvider->getKeys());
    }

    public function testWithSort(): void
    {
        /** with sql statments select */
        $dataProvider = new SqlDataProvider(
            $this->getConnection(),
            'SELECT * FROM {{customer}} ORDER BY id DESC'
        );

        $models = $dataProvider->getModels();

        foreach ($models as $model) {
            $ids[] = $model['id'];
        }

        $this->assertEquals([3, 2, 1], $ids);

        /** with {@see Sort::class} options {@see withSort()} */
        $ids = [];
        $dataProvider = new SqlDataProvider($this->getConnection(), 'SELECT * FROM {{customer}}');
        $dataProvider->withSort((new Sort())->attributes(['id'])->defaultOrder(['id' => ['default' => 'desc']]));

        $models = $dataProvider->getModels();

        foreach ($models as $model) {
            $ids[] = $model['id'];
        }

        $this->assertEquals([3, 2, 1], $ids);
    }

    public function testTotalCount(): void
    {
        $dataProvider = new SqlDataProvider($this->getConnection(), 'SELECT * FROM {{customer}}');
        $this->assertEquals(3, $dataProvider->getTotalCount());
    }

    public function testTotalCountWithParams(): void
    {
        $dataProvider = new SqlDataProvider(
            $this->getConnection(),
            'SELECT * FROM {{customer}} WHERE [[id]] > :minimum',
            [':minimum' => -1]
        );
        $this->assertEquals(3, $dataProvider->getTotalCount());
    }
}
