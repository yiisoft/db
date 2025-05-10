<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Driver\Pdo;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\TestTrait;

class PdoServerInfoTest extends TestCase
{
    use TestTrait;

    public function testGetVersion(): void
    {
        $db = $this->getConnection();
        $serverInfo = $db->getServerInfo();

        $this->assertIsString($serverInfo->getVersion());
    }
}
