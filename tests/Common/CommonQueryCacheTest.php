<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractQueryCacheTest;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group mssql
 * @group mysql
 * @group pgsql
 * @group oracle
 * @group sqlite
 */
abstract class CommonQueryCacheTest extends AbstractQueryCacheTest
{
    use TestTrait;

    public function testCommand(): void
    {
        $db = $this->getConnectionWithData();

        $db->queryCacheEnable(true);
        $command = $db->createCommand(
            <<<SQL
            SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id
            SQL,
        );

        $this->assertSame('user1', $command->bindValue(':id', 1)->queryScalar());

        $update = $db->createCommand(
            <<<SQL
            UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id
            SQL,
        );
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();

        $this->assertSame('user11', $command->bindValue(':id', 1)->queryScalar());

        $db->cache(function (ConnectionPDOInterface $db) use ($command, $update) {
            $this->assertSame('user2', $command->bindValue(':id', 2)->queryScalar());

            $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();

            $this->assertSame('user2', $command->bindValue(':id', 2)->queryScalar());

            $db->noCache(function () use ($command) {
                $this->assertEquals('user22', $command->bindValue(':id', 2)->queryScalar());
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
        $command = $db->createCommand(
            <<<SQL
            SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id
            SQL,
        )->cache();

        $this->assertSame('user11', $command->bindValue(':id', 1)->queryScalar());

        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();

        $this->assertSame('user11', $command->bindValue(':id', 1)->queryScalar());
        $this->assertSame('user1', $command->noCache()->bindValue(':id', 1)->queryScalar());

        $command = $db->createCommand(
            <<<SQL
            SELECT [[name]] FROM {{customer}} WHERE [[id]] = :id
            SQL,
        );

        $db->cache(function () use ($command) {
            $this->assertSame('user11', $command->bindValue(':id', 1)->queryScalar());
            $this->assertSame('user1', $command->noCache()->bindValue(':id', 1)->queryScalar());
        }, 10);
    }

    public function testQueryCacheWithQuery()
    {
        $db = $this->getConnectionWithData();

        $db->queryCacheEnable(true);
        $query = $this->getQuery($db)->select(['name'])->from('customer');
        $update = $db->createCommand(
            <<<SQL
            UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id
            SQL,
        );

        $this->assertSame('user1', $query->where(['id' => 1])->scalar(), 'Asserting initial value');

        /* No cache */
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();

        $this->assertSame(
            'user11',
            $query->where(['id' => 1])->scalar(),
            'Query reflects DB changes when caching is disabled'
        );

        /* Connection cache */
        $db->cache(function (ConnectionPDOInterface $db) use ($query, $update) {
            $this->assertSame('user2', $query->where(['id' => 2])->scalar(), 'Asserting initial value for user #2');

            $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();

            $this->assertSame(
                'user2',
                $query->where(['id' => 2])->scalar(),
                'Query does NOT reflect DB changes when wrapped in connection caching'
            );

            $db->noCache(function () use ($query) {
                $this->assertSame(
                    'user22',
                    $query->where(['id' => 2])->scalar(),
                    'Query reflects DB changes when wrapped in connection caching and noCache simultaneously'
                );
            });

            $this->assertSame(
                'user2',
                $query->where(['id' => 2])->scalar(),
                'Cache does not get changes after getting newer data from DB in noCache block.'
            );
        }, 10);

        $db->queryCacheEnable(false);

        $db->cache(function () use ($query, $update) {
            $this->assertSame(
                'user22',
                $query->where(['id' => 2])->scalar(),
                'When cache is disabled for the whole connection, Query inside cache block does not get cached'
            );

            $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();

            $this->assertSame('user2', $query->where(['id' => 2])->scalar());
        }, 10);

        $db->queryCacheEnable(true);
        $query->cache();

        $this->assertSame('user11', $query->where(['id' => 1])->scalar());

        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();

        $this->assertSame(
            'user11',
            $query->where(['id' => 1])->scalar(),
            'When both Connection and Query have cache enabled, we get cached value'
        );
        $this->assertSame(
            'user1',
            $query->noCache()->where(['id' => 1])->scalar(),
            'When Query has disabled cache, we get actual data'
        );

        $db->cache(function () use ($query) {
            $this->assertSame('user1', $query->noCache()->where(['id' => 1])->scalar());
            $this->assertSame('user11', $query->cache()->where(['id' => 1])->scalar());
        }, 10);
    }
}
