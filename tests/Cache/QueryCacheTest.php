<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class QueryCacheTest extends TestCase
{
    use TestTrait;

    public function testConstruct(): void
    {
        $queryCache = new QueryCache(DbHelper::getCache());

        $this->assertInstanceOf(CacheInterface::class, Assert::getInaccessibleProperty($queryCache, 'cache'));
    }

    public function testInfo(): void
    {
        $queryCache = new QueryCache(DbHelper::getCache());

        $tagDependency = new TagDependency('tag');
        $queryCache->setInfo([3600, $tagDependency]);

        $this->assertIsArray($queryCache->info(null));
        $this->assertIsArray($queryCache->info(3600));
    }

    public function testIsEnabled(): void
    {
        $queryCache = new QueryCache(DbHelper::getCache());

        $this->assertTrue($queryCache->isEnabled());
    }

    public function testRemoveLastInfo(): void
    {
        $queryCache = new QueryCache(DbHelper::getCache());

        $tagDependency = new TagDependency('tag');
        $queryCache->setInfo([3600, $tagDependency]);

        $this->assertIsArray($queryCache->info(null));
        $this->assertIsArray($queryCache->info(3600));

        $queryCache->removeLastInfo();

        $this->assertNull($queryCache->info(null));
        $this->assertIsArray($queryCache->info(3600));
    }

    public function testSetDuration(): void
    {
        $queryCache = new QueryCache(DbHelper::getCache());

        $queryCache->setDuration(10);

        $this->assertSame(10, $queryCache->getDuration());
    }

    public function testSetEnable(): void
    {
        $queryCache = new QueryCache(DbHelper::getCache());

        $queryCache->setEnable(false);

        $this->assertFalse($queryCache->isEnabled());
    }

    public function testSetInfo(): void
    {
        $queryCache = new QueryCache(DbHelper::getCache());

        $queryCache->setInfo('test');

        $this->assertSame(['test'], Assert::getInaccessibleProperty($queryCache, 'info'));

        $queryCache->setInfo(['test2']);

        $this->assertSame(['test', ['test2']], Assert::getInaccessibleProperty($queryCache, 'info'));
    }
}
