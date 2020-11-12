<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;

use function array_pop;
use function end;
use function is_array;

final class ConnectionCache
{
    private CacheInterface $schemaCache;
    private bool $enableSchemaCache = true;
    private int $schemaCacheDuration = 3600;
    private array $schemaCacheExclude = [];
    private bool $enableQueryCache = true;
    public array $queryCacheInfo = [];
    private int $queryCacheDuration = 3600;

    public function __construct(CacheInterface $schemaCache)
    {
        $this->schemaCache = $schemaCache;
    }

    public function getCacheKey(array $key): string
    {
        $jsonKey = json_encode($key, JSON_THROW_ON_ERROR);

        return md5($jsonKey);
    }

    public function getQueryCacheDuration(): ?int
    {
        return $this->queryCacheDuration;
    }

    public function getQueryCacheInfo(): array
    {
        return $this->queryCacheInfo;
    }

    public function getSchemaCache(): CacheInterface
    {
        return $this->schemaCache;
    }

    public function getSchemaCacheDuration(): int
    {
        return $this->schemaCacheDuration;
    }

    public function getSchemaCacheExclude(): array
    {
        return $this->schemaCacheExclude;
    }

    public function isQueryCacheEnabled(): bool
    {
        return $this->enableQueryCache;
    }

    public function isSchemaCacheEnabled(): bool
    {
        return $this->enableSchemaCache;
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
    public function queryCacheInfo(?int $duration, Dependency $dependency = null): ?array
    {
        $result = null;

        if ($this->enableQueryCache) {
            $info = end($this->queryCacheInfo);

            if (is_array($info)) {
                if ($duration === null) {
                    $duration = $info[0];
                }

                if ($dependency === null) {
                    $dependency = $info[1];
                }
            }

            if ($duration === 0 || $duration > 0) {
                if ($this->schemaCache instanceof CacheInterface) {
                    $result = [$this->schemaCache, $duration, $dependency];
                }
            }
        }

        return $result;
    }

    public function queryCacheInfoArrayPop(): void
    {
        array_pop($this->queryCacheInfo);
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
    public function setEnableQueryCache(bool $value): void
    {
        $this->enableQueryCache = $value;
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
    public function setEnableSchemaCache(bool $value): void
    {
        $this->enableSchemaCache = $value;
    }

    public function setQueryCacheInfo($value): void
    {
        $this->queryCacheInfo[] = $value;
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
    public function setQueryCacheDuration(int $value): void
    {
        $this->queryCacheDuration = $value;
    }

    /**
     * Number of seconds that table metadata can remain valid in cache. Use 0 to indicate that the cached data will
     * never expire.
     *
     * @param int $value
     *
     * {@see setEnableSchemaCache()}
     */
    public function setSchemaCacheDuration(int $value): void
    {
        $this->schemaCacheDuration = $value;
    }

    /**
     * List of tables whose metadata should NOT be cached. Defaults to empty array. The table names may contain schema
     * prefix, if any. Do not quote the table names.
     *
     * @param array $value
     *
     * {@see setEnableSchemaCache()}
     */
    public function setSchemaCacheExclude(array $value): void
    {
        $this->schemaCacheExclude = $value;
    }
}
