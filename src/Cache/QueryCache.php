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
    private bool $enabled = true;
    public array $info = [];
    private int $duration = 3600;
    private CacheKeyNormalizer $keyNormalizer;

    public function __construct(CacheInterface $cache, CacheKeyNormalizer $keyNormalizer)
    {
        $this->cache = $cache;
        $this->keyNormalizer = $keyNormalizer;
    }

    public function normalize($key): string
    {
        return $this->keyNormalizer->normalize($key);
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Returns the current query cache information.
     *
     * This method is used internally by {@see Command}.
     *
     * @param int|null $duration the preferred caching duration. If null, it will be ignored.
     * @param Dependency|null $dependency the preferred caching dependency. If null, it will be ignored.
     *
     * @return array|null the current query cache information, or null if query cache is not enabled.
     */
    public function info(?int $duration, Dependency $dependency = null): ?array
    {
        $result = null;

        if ($this->enabled) {
            $info = end($this->info);

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

    public function removeLastInfo(): void
    {
        array_pop($this->info);
    }

    /**
     * Whether to enable query caching. Note that in order to enable query caching, a valid cache component as specified
     * must be enabled and {@see enabled} must be set true. Also, only the results of the queries enclosed within
     * {@see cache()} will be cached.
     *
     * @param bool $value
     *
     * {@see cache()}
     * {@see noCache()}
     */
    public function setEnable(bool $value): void
    {
        $this->enabled = $value;
    }

    public function setInfo($value): void
    {
        $this->info[] = $value;
    }

    /**
     * The default number of seconds that query results can remain valid in cache. Defaults to 3600, meaning 3600
     * seconds, or one hour. Use 0 to indicate that the cached data will never expire. The value of this property will
     * be used when {@see cache()} is called without a cache duration.
     *
     * @param int $value
     *
     * {@see cache()}
     */
    public function setDuration(int $value): void
    {
        $this->duration = $value;
    }
}
