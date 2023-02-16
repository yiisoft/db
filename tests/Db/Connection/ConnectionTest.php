<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Connection;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\AbstractConnectionTest;
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

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Schema::loadTableSchema is not supported by this DBMS.'
        );

        $db->getTableSchema('non_existing_table');
    }

    public function testSerialized(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        parent::testSerialized();
    }
}
