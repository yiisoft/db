<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use InvalidArgumentException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\Exists;

/**
 * @group db
 */
final class ExistsTest extends TestCase
{
    public function testConstructor(): void
    {
        $query = (new Query($this->createMock(ConnectionInterface::class)))
            ->select('id')
            ->from('users')
            ->where(['active' => 1]);
        $existCondition = new Exists('EXISTS', $query);

        $this->assertSame('EXISTS', $existCondition->operator);
        $this->assertSame($query, $existCondition->query);
    }

    public function testFromArrayDefinition(): void
    {
        $query = (new Query($this->createMock(ConnectionInterface::class)))
            ->select('id')
            ->from('users')
            ->where(['active' => 1]);
        $existCondition = Exists::fromArrayDefinition('EXISTS', [$query]);

        $this->assertSame('EXISTS', $existCondition->operator);
        $this->assertSame($query, $existCondition->query);
    }

    public function testFromArrayDefinitionExceptionQuery(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sub query for EXISTS operator must be a Query object.');

        Exists::fromArrayDefinition('EXISTS', []);
    }
}
