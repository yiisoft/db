<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Cache;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Cache\NullCache;

/**
 * @group db
 */
final class NullCacheTest extends TestCase
{
    public function testBase(): void
    {
        $cache = new NullCache();

        $this->assertTrue($cache->set('key', 'value'));
        $this->assertFalse($cache->has('key'));
        $this->assertNull($cache->get('key'));
        $this->assertSame('default', $cache->get('key', 'default'));
        $this->assertTrue($cache->delete('key'));
        $this->assertTrue($cache->clear());
    }

    public function testMultiple(): void
    {
        $cache = new NullCache();

        $this->assertTrue($cache->setMultiple(['a' => 1, 'b' => 2]));
        $this->assertSame(['a' => 'default', 'b' => 'default'], $cache->getMultiple(['a', 'b'], 'default'));
        $this->assertTrue($cache->deleteMultiple(['a', 'b']));
    }
}
