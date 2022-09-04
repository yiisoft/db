<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use DateInterval;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Dependency\TagDependency;

/**
 * The cache application component that is used to cache the table metadata.
 */
final class SchemaCache
{
    private bool $enabled = true;
    private int|null $duration = 3600;
    private array $exclude = [];

    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * Remove a value with the specified key from cache.
     *
     * @param mixed $key A key identifying the value to be deleted from cache.
     */
    public function remove(mixed $key): void
    {
        $this->cache->remove($key);
    }

    public function getOrSet(
        mixed $key,
        mixed $value = null,
        DateInterval|int|null $ttl = null,
        Dependency $dependency = null
    ): mixed {
        return $this->cache->getOrSet(
            $key,
            static fn () => $value,
            $ttl,
            $dependency,
        );
    }

    public function set(
        mixed $key,
        mixed $value,
        DateInterval|int $ttl = null,
        Dependency $dependency = null
    ): void {
        $this->remove($key);
        $this->getOrSet($key, $value, $ttl, $dependency);
    }

    /**
     * Return number of seconds that table metadata can remain valid in cache.
     *
     * @return int|null
     */
    public function getDuration(): int|null
    {
        return $this->duration;
    }

    /**
     * Return true if the table is excluded from cache the table metadata.
     *
     * @param string $value The table name.
     *
     * @return bool
     */
    public function isExcluded(string $value): bool
    {
        return in_array($value, $this->exclude, true);
    }

    /**
     * Invalidates all the cached values that are associated with any of the specified.
     *
     * @param string $cacheTag The cache tag used to identify the values to be invalidated.
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
     * specified must be enabled and must be set true.
     *
     * @param bool $value Whether to enable schema caching.
     *
     * {@see setDuration()}
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
     * @param int|null $value The number of seconds that table metadata can remain valid in cache.
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
     * @param array $value The table names.
     *
     * {@see setEnable()}
     */
    public function setExclude(array $value): void
    {
        $this->exclude = $value;
    }
}
