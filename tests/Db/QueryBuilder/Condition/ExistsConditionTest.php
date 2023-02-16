<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\ExistsCondition;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ExistsConditionTest extends TestCase
{
    public function testConstructor(): void
    {
        $query = (new Query($this->createMock(ConnectionInterface::class)))
            ->select('id')
            ->from('users')
            ->where(['active' => 1]);
        $existCondition = new ExistsCondition('EXISTS', $query);

        $this->assertSame('EXISTS', $existCondition->getOperator());
        $this->assertSame($query, $existCondition->getQuery());
    }

    public function testFromArrayDefinition(): void
    {
        $query = (new Query($this->createMock(ConnectionInterface::class)))
            ->select('id')
            ->from('users')
            ->where(['active' => 1]);
        $existCondition = ExistsCondition::fromArrayDefinition('EXISTS', [$query]);

        $this->assertSame('EXISTS', $existCondition->getOperator());
        $this->assertSame($query, $existCondition->getQuery());
    }

    public function testFromArrayDefinitionExceptionQuery(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sub query for EXISTS operator must be a Query object.');

        ExistsCondition::fromArrayDefinition('EXISTS', []);
    }
}
