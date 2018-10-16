<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db\tests\unit;

use yii\db\data\SqlDataProvider;

/**
 * @group data
 */
class SqlDataProviderTest extends DatabaseTestCase
{
    protected $driverName = 'sqlite';

    public function testGetModels()
    {
        $dataProvider = new SqlDataProvider();
        $dataProvider->sql = 'select * from `customer`';
        $dataProvider->db = $this->getConnection();
        $this->assertCount(3, $dataProvider->getModels());
    }

    public function testTotalCount()
    {
        $dataProvider = new SqlDataProvider();
        $dataProvider->sql = 'select * from `customer`';
        $dataProvider->db = $this->getConnection();
        $this->assertEquals(3, $dataProvider->getTotalCount());
    }

    public function testTotalCountWithParams()
    {
        $dataProvider = new SqlDataProvider();
        $dataProvider->sql = 'select * from `customer` where id > :minimum';
        $dataProvider->params = [
            ':minimum' => -1,
        ];
        $dataProvider->db = $this->getConnection();
        $this->assertEquals(3, $dataProvider->getTotalCount());
    }
}
