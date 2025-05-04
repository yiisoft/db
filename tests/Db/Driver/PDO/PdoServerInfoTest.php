<?php

declare(strict_types=1);

namespace Driver\PDO;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\Support\TestTrait;

class PdoServerInfoTest extends TestCase
{
    use TestTrait;

    public function testGetTimezone(): void
    {
        $db = $this->getConnection();
        $serverInfo = $db->getServerInfo();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Driver\Pdo\PdoServerInfo::getTimezone is not supported by this DBMS.');

        $serverInfo->getTimezone();
    }

    public function testGetVersion(): void
    {
        $db = $this->getConnection();
        $serverInfo = $db->getServerInfo();

        $this->assertIsString($serverInfo->getVersion());

        $db->close();
    }
}
