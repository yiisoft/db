<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Connection;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
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
            'Yiisoft\Db\Tests\Support\Stubs\Schema::loadTableSchema() is not supported by core-db.'
        );

        $db->getTableSchema('non_existing_table');
    }

    public function testNestedTransactionNotSupported(): void
    {
        $db = $this->getConnection();

        $db->setEnableSavepoint(false);

        $this->assertFalse($db->isSavepointEnabled());

        $db->transaction(
            function (ConnectionPDOInterface $db) {
                $this->assertNotNull($db->getTransaction());
                $this->expectException(NotSupportedException::class);

                $db->beginTransaction();
            }
        );
    }
}
