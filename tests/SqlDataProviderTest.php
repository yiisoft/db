<?php
declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Db\Data\SqlDataProvider;

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
