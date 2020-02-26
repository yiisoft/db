<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Querys\Query;
use Yiisoft\Db\Expressions\Expression;
use Yiisoft\Db\Tests\QueryTest as AbstractQueryTest;

final class QueryTest extends AbstractQueryTest
{
    protected ?string $driverName = 'mysql';

    /**
     * Tests MySQL specific syntax for index hints.
     */
    public function testQueryIndexHint(): void
    {
        $query = (new Query($this->getConnection()))->from([new Expression('{{%customer}} USE INDEX (primary)')]);

        $row = $query->one();

        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('email', $row);
    }

    public function testLimitOffsetWithExpression(): void
    {
        $query = (new Query($this->getConnection()))->from('customer')->select('id')->orderBy('id');

        // In MySQL limit and offset arguments must both be non negative integer constant
        $query->limit(new Expression('2'))->offset(new Expression('1'));

        $result = $query->column();

        $this->assertCount(2, $result);
        $this->assertContains("2", $result);
        $this->assertContains("3", $result);
        $this->assertNotContains("1", $result);
    }
}
