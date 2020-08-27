<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

use Yiisoft\Db\Data\DataReaderProvider;

trait TestDataReaderProviderTrait
{
    public function testGetModels(): void
    {
        $dataProvider = (new DataReaderProvider())
            ->sql('select * from customer')
            ->db($this->getConnection());

        $this->assertCount(3, $dataProvider->getModels());
    }

    public function testTotalCount(): void
    {
        $dataProvider = (new DataReaderProvider())
            ->sql('select * from customer')
            ->db($this->getConnection());

        $this->assertEquals(3, $dataProvider->getTotalCount());
    }

    public function testTotalCountWithParams(): void
    {
        $dataProvider = (new DataReaderProvider())
            ->sql('select * from customer where id > :minimum')
            ->params([':minimum' => -1])
            ->db($this->getConnection());

        $this->assertEquals(3, $dataProvider->getTotalCount());
    }
}
