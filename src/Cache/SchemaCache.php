<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Dependency\TagDependency;

/**
 * The cache application component that is used to cache the table metadata.
 */
final class SchemaCache
{
    private CacheInterface $cache;
    private bool $enabled = true;
    private ?int $duration = 3600;
    private array $exclude = [];

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Remove a value with the specified key from cache.
     *
     * @param mixed $key a key identifying the value to be deleted from cache.
     */
    public function remove($key): void
    {
        $this->cache->remove($key);
    }

    public function getOrSet($key, $value = null, $ttl = null, Dependency $dependency = null)
    {
        return $this->cache->getOrSet(
            $key,
            static fn () => $value,
            $ttl,
            $dependency,
        );
    }

    public function set($key, $value, $ttl = null, Dependency $dependency = null)
    {
        $this->remove($key);
        $this->getOrSet($key, $value, $ttl, $dependency);
    }

    /**
     * Return number of seconds that table metadata can remain valid in cache.
     *
     * @return int|null
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * Return true if the table is excluded from cache the table metadata.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isExcluded(string $value): bool
    {
        return in_array($value, $this->exclude, true);
    }

    /**
     * Invalidates all the cached values that are associated with any of the specified {@see tags}.
     *
     * @param string $cacheTag
     */
    public function invalidate(string $cacheTag): void
    {
        TagDependency::invalidate($this->cache, $cacheTag);
    }

    /**
     * Return true if SchemaCache is active.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Whether to enable schema caching. Note that in order to enable truly schema caching, a valid cache component as
     * specified must be enabled and {@see setEnable()} must be set true.
     *
     * @param bool $value
     *
     * {@see setduration()}
     * {@see setExclude()}
     */
    public function setEnable(bool $value): void
    {
        $this->enabled = $value;
    }

    /**
     * Number of seconds that table metadata can remain valid in cache. Use 'null' to indicate that the cached data will
     * never expire.
     *
     * @param int|null $value
     *
     * {@see setEnable()}
     */
    public function setDuration(?int $value): void
    {
        $this->duration = $value;
    }

    /**
     * List of tables whose metadata should NOT be cached. Defaults to empty array. The table names may contain schema
     * prefix, if any. Do not quote the table names.
     *
     * @param array $value
     *
     * {@see setEnable()}
     */
    public function setExclude(array $value): void
    {
        $this->exclude = $value;
    }
}
