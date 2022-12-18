<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Tests\Support\Stub\PDODriver;

trait TestTrait
{
    private string $dsn = 'sqlite::memory:';

    protected function getConnection(bool $fixture = false): ConnectionPDOInterface
    {
        $db = new Stub\Connection(
            new PDODriver($this->dsn),
            DbHelper::getQueryCache(),
            DbHelper::getSchemaCache(),
        );

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/db.sql');
        }

        return $db;
    }

    protected function getDriverName(): string
    {
        return 'db';
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }
}
