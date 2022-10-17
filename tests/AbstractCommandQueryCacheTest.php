<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;

abstract class AbstractCommandQueryCacheTest extends TestCase
{
    public function testQueryCache(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');

        $this->assertSame('user1', $command->bindValue(':id', 1)->queryScalar());

        $update = $db->createCommand('UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id');
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();

        $this->assertSame('user11', $command->bindValue(':id', 1)->queryScalar());

        $db->cache(function (ConnectionInterface $db) use ($command, $update) {
            $this->assertSame('user2', $command->bindValue(':id', 2)->queryScalar());

            $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();

            $this->assertSame('user2', $command->bindValue(':id', 2)->queryScalar());

            $db->noCache(function () use ($command) {
                $this->assertSame('user22', $command->bindValue(':id', 2)->queryScalar());
            });

            $this->assertSame('user2', $command->bindValue(':id', 2)->queryScalar());
        }, 10);

        $db->queryCacheEnable(false);

        $db->cache(function () use ($command, $update) {
            $this->assertSame('user22', $command->bindValue(':id', 2)->queryScalar());
            $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();
            $this->assertSame('user2', $command->bindValue(':id', 2)->queryScalar());
        }, 10);

        $db->queryCacheEnable(true);
        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id')->cache();

        $this->assertSame('user11', $command->bindValue(':id', 1)->queryScalar());

        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();

        $this->assertSame('user11', $command->bindValue(':id', 1)->queryScalar());
        $this->assertSame('user1', $command->noCache()->bindValue(':id', 1)->queryScalar());

        $command = $db->createCommand('SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id');
        $db->cache(function () use ($command) {
            $this->assertSame('user11', $command->bindValue(':id', 1)->queryScalar());
            $this->assertSame('user1', $command->noCache()->bindValue(':id', 1)->queryScalar());
        }, 10);
    }
}
