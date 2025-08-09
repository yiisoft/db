<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Driver\Pdo\PdoDriverInterface;
use Yiisoft\Db\Tests\Support\Stub\PdoDriver;

use function str_replace;

trait TestTrait
{
    private string $dsn = 'sqlite::memory:';

    protected function getConnection(bool $fixture = false): PdoConnectionInterface
    {
        $db = new Stub\Connection($this->getDriver(), DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . '/Fixture/db.sql');
        }

        return $db;
    }

    protected static function getDb(): PdoConnectionInterface
    {
        return new Stub\Connection(new PdoDriver('sqlite::memory:'), DbHelper::getSchemaCache());
    }

    protected function getDriver(): PdoDriverInterface
    {
        return new PdoDriver($this->dsn);
    }

    protected static function getDriverName(): string
    {
        return 'db';
    }

    protected static function replaceQuotes(string $sql): string
    {
        return str_replace(['[[', ']]'], ['[', ']'], $sql);
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }
}
