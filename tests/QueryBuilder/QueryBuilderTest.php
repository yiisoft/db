<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class QueryBuilderTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::buildFrom()
     */
    public function testBuildFrom(mixed $table, string $expectedSql, array $expectedParams = []): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from($table);
        $queryBuilder = $db->getQueryBuilder();

        [$sql, $params] = $queryBuilder->build($query);

        $this->assertSame($expectedSql, $sql);
        $this->assertSame($expectedParams, $params);
    }
}
