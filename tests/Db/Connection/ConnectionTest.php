<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Connection;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractConnectionTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Stub\ColumnFactory;
use Yiisoft\Db\Tests\Support\Stub\StubConnection;
use Yiisoft\Db\Tests\Support\Stub\StubPdoDriver;
use Yiisoft\Db\Tests\Support\TestTrait;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

/**
 * @group db
 */
final class ConnectionTest extends TestCase
{
    public function testGetTableSchema(): void
    {
        $db = $this->createConnection();

        $this->assertNull($db->getTableSchema('non_existing_table'));
    }

    public function testConstructColumnFactory(): void
    {
        $columnFactory = new ColumnFactory();

        $db = $this->createConnection($columnFactory);

        $this->assertSame($columnFactory, $db->getColumnFactory());
    }

    public function testCreateQuery(): void
    {
        $db = $this->createConnection();

        $this->assertInstanceOf(Query::class, $db->createQuery());
    }

    #[TestWith(['columns' => 'column1'])]
    #[TestWith(['columns' => 'now()'])]
    #[TestWith(['columns' => true])]
    #[TestWith(['columns' => 1])]
    #[TestWith(['columns' => 1.2])]
    #[TestWith(['columns' => new Expression('now()')])]
    #[TestWith(['columns' => ['column1', 'now()', new Expression('now()')]])]
    public function testSelect(array|bool|float|int|string|ExpressionInterface $columns, ?string $option = null): void
    {
        $db = $this->createConnection();

        Assert::objectsEquals($db->select($columns, $option), $db->createQuery()->select($columns, $option));
    }

    public function testSelectWithoutParams(): void
    {
        $db = $this->createConnection();

        Assert::objectsEquals($db->select(), $db->createQuery());
    }

    private function createConnection(?ColumnFactory $columnFactory = null): StubConnection
    {
        return new StubConnection(
            new StubPdoDriver('sqlite::memory:'),
            new SchemaCache(
                new MemorySimpleCache(),
            ),
            $columnFactory,
        );
    }
}
