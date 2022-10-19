<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

abstract class AbstractQueryCacheTest extends TestCase
{
    public function testQueryCache()
    {
        $db = $this->getConnection();

        $query = (new Query($db))->select(['name'])->from('customer');
        $update = $db->createCommand('UPDATE {{customer}} SET [[name]] = :name WHERE [[id]] = :id');

        $this->assertEquals('user1', $query->where(['id' => 1])->scalar(), 'Asserting initial value');

        /* No cache */
        $update->bindValues([':id' => 1, ':name' => 'user11'])->execute();

        $this->assertEquals(
            'user11',
            $query->where(['id' => 1])->scalar(),
            'Query reflects DB changes when caching is disabled'
        );

        /* Connection cache */
        $db->cache(function (ConnectionInterface $db) use ($query, $update) {
            $this->assertEquals(
                'user2',
                $query->where(['id' => 2])->scalar(),
                'Asserting initial value for user #2'
            );

            $update->bindValues([':id' => 2, ':name' => 'user22'])->execute();

            $this->assertEquals(
                'user2',
                $query->where(['id' => 2])->scalar(),
                'Query does NOT reflect DB changes when wrapped in connection caching'
            );

            $db->noCache(function () use ($query) {
                $this->assertEquals(
                    'user22',
                    $query->where(['id' => 2])->scalar(),
                    'Query reflects DB changes when wrapped in connection caching and noCache simultaneously'
                );
            });

            $this->assertEquals(
                'user2',
                $query->where(['id' => 2])->scalar(),
                'Cache does not get changes after getting newer data from DB in noCache block.'
            );
        }, 10);

        $db->queryCacheEnable(false);

        $db->cache(function () use ($query, $update) {
            $this->assertEquals(
                'user22',
                $query->where(['id' => 2])->scalar(),
                'When cache is disabled for the whole connection, Query inside cache block does not get cached'
            );

            $update->bindValues([':id' => 2, ':name' => 'user2'])->execute();

            $this->assertEquals('user2', $query->where(['id' => 2])->scalar());
        }, 10);

        $db->queryCacheEnable(true);
        $query->cache();

        $this->assertEquals('user11', $query->where(['id' => 1])->scalar());

        $update->bindValues([':id' => 1, ':name' => 'user1'])->execute();

        $this->assertEquals(
            'user11',
            $query->where(['id' => 1])->scalar(),
            'When both Connection and Query have cache enabled, we get cached value'
        );
        $this->assertEquals(
            'user1',
            $query->noCache()->where(['id' => 1])->scalar(),
            'When Query has disabled cache, we get actual data'
        );

        $db->cache(function () use ($query) {
            $this->assertEquals('user1', $query->noCache()->where(['id' => 1])->scalar());
            $this->assertEquals('user11', $query->cache()->where(['id' => 1])->scalar());
        }, 10);
    }
}
