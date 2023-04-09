<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Tests\Support\Stub\PdoDriver;

trait TestTrait
{
    private string $dsn = 'sqlite::memory:';

    protected function getConnection(bool $fixture = false): PdoConnectionInterface
    {
        $db = new Stub\Connection(new PdoDriver($this->dsn), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/db.sql');
        }

        return $db;
    }

    protected static function getDb(): PdoConnectionInterface
    {
        return new Stub\Connection(new PdoDriver('sqlite::memory:'), DbHelper::getSchemaCache());
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
