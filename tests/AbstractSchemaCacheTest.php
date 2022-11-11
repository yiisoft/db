<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractSchemaCacheTest extends TestCase
{
    use TestTrait;

    public function testConstruct(): void
    {
        $queryCache = new SchemaCache($this->getCache());

        $this->assertInstanceOf(CacheInterface::class, Assert::getInaccessibleProperty($queryCache, 'cache'));
    }

    public function testInvalidate(): void
    {
        $schemaCache = $this->getSchemaCache();

        $schemaCache->set('key', 'value', 3600, new TagDependency('tag'));

        $this->assertSame('value', $schemaCache->getOrSet('key'));

        $schemaCache->invalidate('tag');

        $this->assertNull($schemaCache->getOrSet('key'));
    }

    public function testSetDuration(): void
    {
        $schemaCache = $this->getSchemaCache();

        $schemaCache->setDuration(3600);

        $this->assertSame(3600, $schemaCache->getDuration());
    }

    public function testSetEnabled(): void
    {
        $schemaCache = $this->getSchemaCache();

        $schemaCache->setEnable(false);

        $this->assertFalse($schemaCache->isEnabled());

        $schemaCache->setEnable(true);

        $this->assertTrue($schemaCache->isEnabled());
    }

    public function testSetExclude(): void
    {
        $schemaCache = $this->getSchemaCache();

        $schemaCache->setExclude(['table1', 'table2']);

        $this->assertSame(['table1', 'table2'], Assert::getInaccessibleProperty($schemaCache, 'exclude'));
    }
}
