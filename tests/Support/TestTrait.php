<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Tests\Support\Stub\PDODriver;

trait TestTrait
{
    protected function getConnection(string ...$fixtures): ConnectionPDOInterface
    {
        $db = new Stub\Connection(
            new PDODriver('sqlite::memory:'),
            DbHelper::getQueryCache(),
            DbHelper::getSchemaCache(),
        );

        foreach ($fixtures as $fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$fixture.sql");
        }

        return $db;
    }
}
