<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Connection;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\AbstractConnectionTest;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Stub\ColumnFactory;
use Yiisoft\Db\Tests\Support\Stub\Connection;
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
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        parent::testSerialized();
    }

    public function testConstructColumnFactory(): void
    {
        $columnFactory = new ColumnFactory();

        $db = new Connection($this->getDriver(), DbHelper::getSchemaCache(), $columnFactory);

        $this->assertSame($columnFactory, $db->getColumnFactory());
    }
}
