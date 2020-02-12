<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Expressions\Expression;
use Yiisoft\Db\Querys\Query;
use Yiisoft\Db\Tests\QueryTest as AbstractQueryTest;

final class QueryTest extends AbstractQueryTest
{
    protected ?string $driverName = 'sqlite';

    public function testUnion(): void
    {
        $db = $this->getConnection();

        $query = new Query($db);

        $query->select(['id', 'name'])
            ->from('item')
            ->union(
                (new Query($db))
                    ->select(['id', 'name'])
                    ->from(['category'])
            );

        $result = $query->all();

        $this->assertNotEmpty($result);
        $this->assertCount(7, $result);
    }

    public function testLimitOffsetWithExpression(): void
    {
        $query = (new Query($this->getConnection()))->from('customer')->select('id')->orderBy('id');

        $query
            ->limit(new Expression('1 + 1'))
            ->offset(new Expression('1 + 0'));

        $result = $query->column();

        $this->assertCount(2, $result);
        $this->assertContains("2", $result);
        $this->assertContains("3", $result);
        $this->assertNotContains("1", $result);
    }
}
