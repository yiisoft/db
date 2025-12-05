<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\NotExists;
use Yiisoft\Db\Tests\Support\TestHelper;

/**
 * @group db
 */
final class NotExistsTest extends TestCase
{
    public function testConstructor(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $query = new Query($db);

        $condition = new NotExists($query);

        $this->assertSame($query, $condition->query);
    }

    public function testFromArrayDefinition(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $query = new Query($db);

        $condition = NotExists::fromArrayDefinition('NOT EXISTS', [$query]);

        $this->assertSame($query, $condition->query);
    }

    public function testFromArrayDefinitionExceptionQuery(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sub-query for NOT EXISTS operator must be a Query object.');
        NotExists::fromArrayDefinition('NOT EXISTS', []);
    }
}
