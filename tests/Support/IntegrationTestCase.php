<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Tests\Support\Stub\StubConnection;
use Yiisoft\Db\Tests\Support\Stub\StubPdoDriver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

abstract class IntegrationTestCase extends BaseTestCase
{
    private static ?ConnectionInterface $connection = null;

    final protected function getSharedConnection(): ConnectionInterface
    {
        $db = self::$connection ??= $this->createConnection();
        $db->getSchema()->refresh();
        return $db;
    }

    final protected function loadFixture(?string $file = null, ?ConnectionInterface $db = null): void
    {
        $file ??= $this->getDefaultFixture();
        $db ??= $this->getSharedConnection();

        $lines = $this->parseDump(file_get_contents($file));

        $db->open();
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->getPdo()->exec($line);
            }
        }
    }

    protected function createConnection(): ConnectionInterface
    {
        return new StubConnection(
            new StubPdoDriver('sqlite::memory:'),
            new SchemaCache(
                new MemorySimpleCache(),
            ),
        );
    }

    /**
     * @return string[]
     */
    protected function parseDump(string $content): array
    {
        return explode(';', $content);
    }

    protected function getDefaultFixture(): string
    {
        return __DIR__ . '/Fixture/db.sql';
    }
}
