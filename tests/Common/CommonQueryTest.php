<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractQueryTest;

abstract class CommonQueryTest extends AbstractQueryTest
{
    public function testColumnWithIndexBy(): void
    {
        $db = $this->getConnection(true);

        $query = (new Query($db))->select('customer.name')->from('customer')->indexBy('customer.id');

        $this->assertSame([1 => 'user1', 2 => 'user2', 3 => 'user3'], $query->column());

        $db->close();
    }

    public function testColumnIndexByWithClosure()
    {
        $db = $this->getConnection(true);

        $result = (new Query($db))
            ->select(['id', 'name'])
            ->from('customer')
            ->indexBy(fn ($row) => $row['id'] * 2)
            ->column();

        $this->assertEquals([2 => '1', 4 => '2', 6 => '3'], $result);

        $db->close();
    }
}
