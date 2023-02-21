<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Cache;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class SchemaCacheTest extends TestCase
{
    use TestTrait;

    public function testInvalidate(): void
    {
        $schemaCache = new SchemaCache(DbHelper::getPsrCache());

        $schemaCache->set('key', 'value', 3600, 'tag');

        $this->assertSame('value', $schemaCache->getOrSet('key'));

        $schemaCache->invalidate('tag');

        $this->assertNull($schemaCache->getOrSet('key'));
    }

    public function testSetDuration(): void
    {
        $schemaCache = new SchemaCache(DbHelper::getPsrCache());

        $schemaCache->setDuration(3600);

        $this->assertSame(3600, $schemaCache->getDuration());
    }

    public function testSetEnabled(): void
    {
        $schemaCache = new SchemaCache(DbHelper::getPsrCache());

        $schemaCache->setEnable(false);

        $this->assertFalse($schemaCache->isEnabled());

        $schemaCache->setEnable(true);

        $this->assertTrue($schemaCache->isEnabled());
    }

    public function testSetExclude(): void
    {
        $schemaCache = new SchemaCache(DbHelper::getPsrCache());

        $schemaCache->setExclude(['table1', 'table2']);

        $this->assertSame(['table1', 'table2'], Assert::getInaccessibleProperty($schemaCache, 'exclude'));
    }
}
