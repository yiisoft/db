<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use InvalidArgumentException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\NotExists;

/**
 * @group db
 */
final class NotExistsTest extends TestCase
{
    public function testConstructor(): void
    {
        $query = new Query($this->createMock(ConnectionInterface::class));

        $condition = new NotExists($query);

        $this->assertSame($query, $condition->query);
    }

    public function testFromArrayDefinition(): void
    {
        $query = new Query($this->createMock(ConnectionInterface::class));

        $condition = NotExists::fromArrayDefinition('NOT EXISTS', [$query]);

        $this->assertSame($query, $condition->query);
    }

    public function testFromArrayDefinitionExceptionQuery(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sub query for NOT EXISTS operator must be a Query object.');
        NotExists::fromArrayDefinition('NOT EXISTS', []);
    }
}
