<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Connection;

use PHPUnit\Framework\Attributes\TestWith;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractConnectionTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Stub\ColumnFactory;
use Yiisoft\Db\Tests\Support\Stub\StubConnection;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConnectionTest extends AbstractConnectionTest
{
    use TestTrait;

    public function testGetTableSchema(): void
    {
        $db = $this->getConnection();

        $this->assertNull($db->getTableSchema('non_existing_table'));
    }

    public function testSerialized(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.',
        );

        parent::testSerialized();
    }

    public function testConstructColumnFactory(): void
    {
        $columnFactory = new ColumnFactory();

        $db = new StubConnection($this->getDriver(), DbHelper::getSchemaCache(), $columnFactory);

        $this->assertSame($columnFactory, $db->getColumnFactory());
    }

    public function testCreateQuery(): void
    {
        $db = $this->getConnection();

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
        $db = $this->getConnection();

        Assert::objectsEquals($db->select($columns, $option), $db->createQuery()->select($columns, $option));
    }

    public function testSelectWithoutParams(): void
    {
        $db = $this->getConnection();

        Assert::objectsEquals($db->select(), $db->createQuery());
    }
}
