<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Driver\Pdo;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\Support\TestHelper;

class PdoServerInfoTest extends TestCase
{
    public function testGetTimezone(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $serverInfo = $db->getServerInfo();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Driver\Pdo\PdoServerInfo::getTimezone is not supported by this DBMS.');

        $serverInfo->getTimezone();
    }

    public function testGetVersion(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $serverInfo = $db->getServerInfo();

        $this->assertIsString($serverInfo->getVersion());
    }
}
