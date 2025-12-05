<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Driver\Pdo;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Tests\Support\Stub\StubConnection;
use Yiisoft\Db\Tests\Support\Stub\StubPdoDriver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

/**
 * @group db
 */
final class PdoConnectionTest extends TestCase
{
    public function testOpenWithEmptyDsn(): void
    {
        $db = $this->createConnection(dsn: '');

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Connection::dsn cannot be empty.');

        $db->open();
    }

    public function testGetLastInsertID(): void
    {
        $db = $this->createConnection();

        $this->expectException(InvalidCallException::class);
        $this->expectExceptionMessage('DB Connection is not active.');

        $db->getLastInsertId();
    }

    public function testQuoteValueString(): void
    {
        $db = $this->createConnection();

        $string = 'test string';

        $this->assertStringContainsString($string, $db->quoteValue($string));
    }

    private function createConnection(string $dsn = 'sqlite::memory:'): StubConnection
    {
        return new StubConnection(
            new StubPdoDriver($dsn),
            new SchemaCache(
                new MemorySimpleCache(),
            ),
        );
    }
}
