<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\CacheKeyNormalizer;

final class SchemaCache
{
    private CacheInterface $cache;
    private bool $enableCache = true;
    private int $cacheDuration = 3600;
    private array $cacheExclude = [];
    private CacheKeyNormalizer $cacheKeyNormalizer;

    public function __construct(CacheInterface $cache, CacheKeyNormalizer $cacheKeyNormalizer)
    {
        $this->cache = $cache;
        $this->cacheKeyNormalizer = $cacheKeyNormalizer;
    }

    public function normalize($key): string
    {
        return $this->cacheKeyNormalizer->normalize($key);
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getCacheDuration(): int
    {
        return $this->cacheDuration;
    }

    public function getCacheExclude(): array
    {
        return $this->cacheExclude;
    }

    public function isCacheEnabled(): bool
    {
        return $this->enableCache;
    }

    /**
     * Whether to enable schema caching. Note that in order to enable truly schema caching, a valid cache component as
     * specified by {@see setSchemaCache()} must be enabled and {@see setEnableSchemaCache()} must be set true.
     *
     * @param bool $value
     *
     * {@see setSchemaCacheDuration()}
     * {@see setSchemaCacheExclude()}
     * {@see setSchemaCache()}
     */
    public function setEnableCache(bool $value): void
    {
        $this->enableCache = $value;
    }

    /**
     * Number of seconds that table metadata can remain valid in cache. Use 0 to indicate that the cached data will
     * never expire.
     *
     * @param int $value
     *
     * {@see setEnableSchemaCache()}
     */
    public function setCacheDuration(int $value): void
    {
        $this->cacheDuration = $value;
    }

    /**
     * List of tables whose metadata should NOT be cached. Defaults to empty array. The table names may contain schema
     * prefix, if any. Do not quote the table names.
     *
     * @param array $value
     *
     * {@see setEnableSchemaCache()}
     */
    public function setCacheExclude(array $value): void
    {
        $this->cacheExclude = $value;
    }
}
