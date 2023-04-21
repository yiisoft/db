<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Cache;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Exception\PsrInvalidArgumentException;
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

        $schemaCache->set('key', 'value', 'tag');

        $this->assertSame('value', $schemaCache->get('key'));

        $schemaCache->invalidate('tag');

        $this->assertNull($schemaCache->get('key'));
    }

    public function testInvalidateWithEmptyTag(): void
    {
        $schemaCache = new SchemaCache(DbHelper::getPsrCache());

        $schemaCache->set('key', 'value');

        $this->assertSame('value', $schemaCache->get('key'));

        $schemaCache->invalidate('');

        $this->assertNotNull($schemaCache->get('key'));
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

        $schemaCache->setEnabled(false);

        $this->assertFalse($schemaCache->isEnabled());

        $schemaCache->setEnabled(true);

        $this->assertTrue($schemaCache->isEnabled());
    }

    public function testSetExclude(): void
    {
        $schemaCache = new SchemaCache(DbHelper::getPsrCache());

        $schemaCache->setExclude(['table1', 'table2']);

        $this->assertSame(['table1', 'table2'], Assert::getInaccessibleProperty($schemaCache, 'exclude'));
    }

    public function testWithFailSetCache(): void
    {
        $cacheMock = $this->createMock(CacheInterface::class);
        $cacheMock->expects(self::once())
            ->method('set')
            ->willReturn(false);

        $schemaCache = new SchemaCache($cacheMock);

        $this->expectException(RuntimeException::class);
        $schemaCache->set('key', 'test');
    }

    public function testInvalidCacheKey(): void
    {
        $resource = fopen('php://memory', 'r');
        $schemaCache = new SchemaCache(DbHelper::getPsrCache());

        $this->expectException(PsrInvalidArgumentException::class);
        $schemaCache->set($resource, 1);
        fclose($resource);
    }
}
