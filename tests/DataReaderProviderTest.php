<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Db\Data\DataReaderProvider;

abstract class DataReaderProviderTest extends DatabaseTestCase
{
    public function testGetModels()
    {
        $dataProvider = (new DataReaderProvider())
            ->sql('select * from customer')
            ->db($this->getConnection());

        $this->assertCount(3, $dataProvider->getModels());
    }

    public function testTotalCount()
    {
        $dataProvider = (new DataReaderProvider())
            ->sql('select * from customer')
            ->db($this->getConnection());

        $this->assertEquals(3, $dataProvider->getTotalCount());
    }

    public function testTotalCountWithParams()
    {
        $dataProvider = (new DataReaderProvider())
            ->sql('select * from customer where id > :minimum')
            ->params([':minimum' => -1])
            ->db($this->getConnection());

        $this->assertEquals(3, $dataProvider->getTotalCount());
    }
}
