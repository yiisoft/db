<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\QueryBuilder\Condition\None;
use Yiisoft\Db\Tests\Support\TestHelper;

use function PHPUnit\Framework\assertSame;

final class NoneTest extends TestCase
{
    public function testQuery(): void
    {
        $query = TestHelper::createSqliteMemoryConnection()
            ->createQuery()
            ->from('test_table')
            ->where(new None());

        $sql = $query->createCommand()->getRawSql();

        assertSame('SELECT * FROM [test_table] WHERE 0=1', $sql);
    }
}
