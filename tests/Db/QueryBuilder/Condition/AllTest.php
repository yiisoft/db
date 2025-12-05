<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\All;
use Yiisoft\Db\Tests\Support\TestHelper;

use function PHPUnit\Framework\assertSame;

final class AllTest extends TestCase
{
    public function testQuery(): void
    {
        $query = TestHelper::createSqliteMemoryConnection()
            ->createQuery()
            ->from('test_table')
            ->where(new All());

        $sql = $query->createCommand()->getRawSql();

        assertSame('SELECT * FROM [test_table]', $sql);
    }
}
