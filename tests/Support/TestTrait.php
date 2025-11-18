<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Tests\Support\Stub\StubPdoDriver;

use function str_replace;

trait TestTrait
{
    protected static function getDb(): PdoConnectionInterface
    {
        return new Stub\StubConnection(
            new StubPdoDriver('sqlite::memory:'),
            TestHelper::createMemorySchemaCache(),
        );
    }

    protected static function replaceQuotes(string $sql): string
    {
        return str_replace(['[[', ']]'], ['[', ']'], $sql);
    }
}
