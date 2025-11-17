<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Connection\ConnectionInterface;

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

    abstract protected function createConnection(): ConnectionInterface;

    /**
     * @return string[]
     */
    protected function parseDump(string $content): array
    {
        return explode(';', $content);
    }

    abstract protected function getDefaultFixture(): string;
}
