<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\CacheKeyNormalizer;
use Yiisoft\Cache\Dependency\Dependency;

use function array_pop;
use function end;
use function is_array;

final class QueryCache
{
    private CacheInterface $cache;
    private bool $enableCache = true;
    public array $cacheInfo = [];
    private int $cacheDuration = 3600;
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

    public function getCacheDuration(): ?int
    {
        return $this->cacheDuration;
    }

    public function getCacheInfo(): array
    {
        return $this->cacheInfo;
    }

    public function isCacheEnabled(): bool
    {
        return $this->enableCache;
    }

    /**
     * Returns the current query cache information.
     *
     * This method is used internally by {@see Command}.
     *
     * @param int|null $duration the preferred caching duration. If null, it will be ignored.
     * @param Dependency|null $dependency the preferred caching dependency. If null, it will be
     * ignored.
     *
     * @return array|null the current query cache information, or null if query cache is not enabled.
     */
    public function cacheInfo(?int $duration, Dependency $dependency = null): ?array
    {
        $result = null;

        if ($this->enableCache) {
            $info = end($this->cacheInfo);

            if (is_array($info)) {
                if ($duration === null) {
                    $duration = $info[0];
                }

                if ($dependency === null) {
                    $dependency = $info[1];
                }
            }

            if ($duration === 0 || $duration > 0) {
                if ($this->cache instanceof CacheInterface) {
                    $result = [$this->cache, $duration, $dependency];
                }
            }
        }

        return $result;
    }

    public function cacheInfoArrayPop(): void
    {
        array_pop($this->cacheInfo);
    }

    /**
     * Whether to enable query caching. Note that in order to enable query caching, a valid cache component as specified
     * by {@see setQueryCache()} must be enabled and {@see enableQueryCache} must be set true. Also, only the results of
     * the queries enclosed within {@see cache()} will be cached.
     *
     * @param bool $value
     *
     * {@see setQueryCache()}
     * {@see cache()}
     * {@see noCache()}
     */
    public function setEnableCache(bool $value): void
    {
        $this->enableCache = $value;
    }

    public function setCacheInfo($value): void
    {
        $this->cacheInfo[] = $value;
    }

    /**
     * The default number of seconds that query results can remain valid in cache. Defaults to 3600, meaning 3600
     * seconds, or one hour. Use 0 to indicate that the cached data will never expire. The value of this property will
     * be used when {@see cache()} is called without a cache duration.
     *
     * @param int $value
     *
     * {@see setEnableQueryCache()}
     * {@see cache()}
     */
    public function setCacheDuration(int $value): void
    {
        $this->cacheDuration = $value;
    }
}
