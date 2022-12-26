<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;

use function array_pop;
use function end;
use function is_array;

/**
 * QueryCache is a class provides a query cache feature for DB connections.
 *
 * The query cache allows you to cache the results of SELECT queries for a certain period of time. This can improve the
 * performance of your application by avoiding the need to execute the same SELECT query multiple times.
 *
 * To use the query cache, you can configure a DB connection to use a QueryCache object as its query cache. When you
 * execute a SELECT query using this connection, the query cache will check if the results of the query have been
 * cached. If they have, it will return the cached results. If not, it will execute the query and cache the results
 * before returning them.
 *
 * You can specify the lifetime of the cache using the {@see duration} property. You can also specify a cache
 * dependency, which determines when the cache should be invalidated. For example, you can use a cache dependency based
 * on the modification time of a table, so that the cache is invalidated whenever the table is updated.
 */
final class QueryCache
{
    private bool $enabled = true;
    public array $info = [];
    private int|null $duration = 3600;

    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * @return int|null The number of seconds that query results can remain valid in cache.
     */
    public function getDuration(): int|null
    {
        return $this->duration;
    }

    /**
     * @return bool True if QueryCache is active.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Returns the current query cache information.
     *
     * This method is used internally by {@see \Yiisoft\Db\Command\Command}.
     *
     * @param int|null $duration The preferred caching duration. If null, it will be ignored.
     * @param Dependency|null $dependency The preferred caching dependency. If null, it will be ignored.
     *
     * @return array|null The current query cache information, or null if query cache is not enabled.
     */
    public function info(int|null $duration, Dependency $dependency = null): array|null
    {
        $result = null;

        if ($this->enabled) {
            /** @var mixed */
            $info = end($this->info);

            if (is_array($info)) {
                if ($duration === null) {
                    /** @var int */
                    $duration = $info[0];
                }

                if ($dependency === null) {
                    /** @var Dependency */
                    $dependency = $info[1];
                }
            }

            if ($duration === 0 || $duration > 0) {
                $result = [$this->cache, $duration, $dependency];
            }
        }

        return $result;
    }

    /**
     * Extract the last element from the end of the QueryCache information.
     */
    public function removeLastInfo(): void
    {
        array_pop($this->info);
    }

    /**
     * Whether to enable query caching. Note that in order to enable query caching, a valid cache component as specified
     * must be enabled and must be set true. Also, only the results of the queries enclosed within will be cached.
     *
     * @param bool $value Whether to enable query caching.
     */
    public function setEnable(bool $value): void
    {
        $this->enabled = $value;
    }

    /**
     * Add an element to the array that QueryCache information.
     *
     * @param mixed $value The value to be added to the array.
     */
    public function setInfo(mixed $value): void
    {
        $this->info[] = $value;
    }

    /**
     * The default number of seconds that query results can remain valid in cache. Defaults to 3600, meaning 3600
     * seconds, or one hour. Use `null` to indicate that the cached data will never expire. The value of this property
     * will be used when is called without a cache duration.
     *
     * @param int|null $value The number of seconds that query results can remain valid in cache.
     */
    public function setDuration(int|null $value): void
    {
        $this->duration = $value;
    }
}
