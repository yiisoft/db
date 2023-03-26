<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;

use function in_array;
use function is_int;
use function is_string;
use function json_encode;
use function json_last_error_msg;
use function mb_strlen;
use function md5;
use function strpbrk;

/**
 * Implements a cache for the database schema information.
 *
 * The {@see \Yiisoft\Db\Schema\AbstractSchema} retrieves information about the database schema from the database server
 * and stores it in the cache for faster access. When the {@see \Yiisoft\Db\Schema\AbstractSchema} needs to retrieve
 * information about the database schema, it first checks the cache using {@see SchemaCache}. If the information is
 * not in the cache, the Schema retrieves it from the database server and stores it in the cache using the
 * {@see SchemaCache}.
 *
 * {@see \Yiisoft\Db\Schema\AbstractSchema} uses this implementation to cache table metadata.
 */
final class SchemaCache
{
    private int|null $duration = 3600;
    private bool $enabled = true;
    private array $exclude = [];

    /**
     * @param CacheInterface $psrCache PSR-16 cache implementation to use.
     *
     * @link https://www.php-fig.org/psr/psr-16/
     */
    public function __construct(private CacheInterface $psrCache)
    {
    }

    /**
     * Remove a value with the specified key from cache.
     *
     * @param mixed $key A key identifying the value to delete from cache.
     *
     * @throws InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function remove(mixed $key): void
    {
        $stringKey = $this->normalize($key);
        $this->psrCache->delete($stringKey);
    }

    /**
     * The method combines retrieving and setting the value identified by the `$key`.
     *
     * It will save the result of `$callable` execution if there is no cache available for the `$key`.
     *
     * @param mixed $key The key identifying the value to cache.
     * @param mixed $value The value to cache.
     * @param DateInterval|int|null $ttl The TTL of this value. If not set, it uses the default value of
     * the PSR cache implementation.
     * @param string|null $cacheTag Tag name to tag cache with.
     *
     * @throws InvalidArgumentException
     * @throws InvalidCallException If cache value isn't set.
     * @throws \Psr\SimpleCache\InvalidArgumentException Thrown if the `$key` or `$ttl` isn't a legal value.
     *
     * @return mixed Result of `$callable` execution.
     */
    public function getOrSet(
        mixed $key,
        mixed $value = null,
        DateInterval|int $ttl = null,
        string $cacheTag = null
    ): mixed {
        $stringKey = $this->normalize($key);

        if ($this->psrCache->has($stringKey)) {
            return $this->psrCache->get($stringKey);
        }

        $result = $this->psrCache->set($stringKey, $value, $ttl);

        if ($result) {
            $this->addToTag($stringKey, $cacheTag);
            return $value;
        }

        throw new InvalidCallException('Cache value not set.');
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store.
     * @param DateInterval|int|null $ttl Optional. Default is to use underlying PSR implementation value.
     *
     * @throws InvalidArgumentException If the $key string isn't a legal value.
     * @throws InvalidCallException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set(
        mixed $key,
        mixed $value,
        DateInterval|int $ttl = null,
        string $cacheTag = null
    ): void {
        $this->remove($key);
        $this->getOrSet($key, $value, $ttl, $cacheTag);
    }

    /**
     * @return int|null The number of seconds that table metadata can remain valid in cache.
     */
    public function getDuration(): int|null
    {
        return $this->duration;
    }

    /**
     * @param string $value The table name.
     *
     * @return bool Whether to exclude the table from caching.
     */
    public function isExcluded(string $value): bool
    {
        return in_array($value, $this->exclude, true);
    }

    /**
     * Invalidates all the cached values associated with any of the specified tags.
     *
     * @param string $cacheTag The cache tag used to identify the values to invalidate.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function invalidate(string $cacheTag): void
    {
        if (empty($cacheTag)) {
            return;
        }

        /** @psalm-var string[] $data */
        $data = $this->psrCache->get($cacheTag, []);

        foreach ($data as $key) {
            $this->psrCache->delete($key);
        }
    }

    /**
     * Return true if SchemaCache is active.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Whether to enable schema caching.
     *
     * @param bool $value Whether to enable schema caching.
     *
     * @see setDuration()
     * @see setExclude()
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
     * @see setEnable()
     */
    public function setDuration(int|null $value): void
    {
        $this->duration = $value;
    }

    /**
     * List of tables not to cache metadata for.
     *
     * Defaults to an empty array. The table names may contain schema prefix, if any. Don't quote the table names.
     *
     * @param array $value The table names.
     *
     * @see setEnable()
     */
    public function setExclude(array $value): void
    {
        $this->exclude = $value;
    }

    /**
     * Normalizes the cache key from a given key.
     *
     * If the given key is a string that doesn't contain characters `{}()/\@:` and no more than 64 characters, then the
     * key will be returned back as it's, integers will be converted to strings.
     *
     * Otherwise, a normalized key is generated by encoding the given key into JSON and applying MD5 hashing.
     *
     * @link https://www.php-fig.org/psr/psr-16/#12-definitions
     *
     * @param mixed $key A key to normalize.
     *
     * @throws InvalidArgumentException For invalid key.
     *
     * @return string The normalized cache key.
     */
    private function normalize(mixed $key): string
    {
        if (is_string($key) || is_int($key)) {
            $key = (string)$key;
            $length = mb_strlen($key, '8bit');
            return (strpbrk($key, '{}()/\@:') || $length < 1 || $length > 64) ? md5($key) : $key;
        }

        $key = json_encode($key);

        if (!$key) {
            throw new InvalidArgumentException('Invalid key. ' . json_last_error_msg());
        }

        return md5($key);
    }

    /**
     * Add key to tag. If tag is empty, do nothing.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function addToTag(string $key, string $cacheTag = null): void
    {
        if (empty($cacheTag)) {
            return;
        }

        /** @psalm-var string[] $data */
        $data = $this->psrCache->get($cacheTag, []);
        $data[] = $key;
        $this->psrCache->set($cacheTag, $data);
    }
}
