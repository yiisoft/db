<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Connection;

use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Tests\AbstractConnectionPDOTest;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConnectionPDOTest extends AbstractConnectionPDOTest
{
    use TestTrait;

    public function testOpenWithEmptyDsn(): void
    {
        $this->setDsn('');
        $db = $this->getConnection();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Connection::dsn cannot be empty.');

        $db->open();
    }

    public function testGetLastInsertID(): void
    {
        $db = $this->getConnection();

        $this->expectException(InvalidCallException::class);
        $this->expectExceptionMessage('DB Connection is not active.');

        $db->getLastInsertID();
    }

    public function testQuoteValueString(): void
    {
        $db = $this->getConnection();

        $string = 'test string';

        $this->assertStringContainsString($string ,$db->quoteValue($string));
    }
}
