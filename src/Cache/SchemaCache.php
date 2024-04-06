<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;
use Yiisoft\Db\Exception\PsrInvalidArgumentException;

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
    private int|null|DateInterval $duration = 3600;
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
     */
    public function remove(mixed $key): void
    {
        $stringKey = $this->normalize($key);
        $this->psrCache->delete($stringKey);
    }

    /**
     * Retrieve value from cache.
     *
     * @param mixed $key The key identifying the value to cache.
     * @throws InvalidArgumentException
     * @return mixed Cache value.
     */
    public function get(mixed $key): mixed
    {
        $stringKey = $this->normalize($key);
        return $this->psrCache->get($stringKey);
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional tag.
     *
     * @param mixed $key The key of the item to store.
     * @param mixed $value The value of the item to store.
     * @param string|null $tag Cache tag.
     *
     * @throws InvalidArgumentException If the $key string isn't a legal value.
     * @throws RuntimeException If cache value isn't set.
     */
    public function set(mixed $key, mixed $value, string $tag = null): void
    {
        $stringKey = $this->normalize($key);

        if ($this->psrCache->set($stringKey, $value, $this->duration)) {
            $this->addToTag($stringKey, $tag);
            return;
        }

        throw new RuntimeException('Cache value not set.');
    }

    /**
     * @return DateInterval|int|null The number of seconds that table metadata can remain valid in cache.
     */
    public function getDuration(): int|null|DateInterval
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
     * @throws InvalidArgumentException
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
    public function setEnabled(bool $value): void
    {
        $this->enabled = $value;
    }

    /**
     * Number of seconds that table metadata can remain valid in cache. Use 'null' to indicate that the cached data will
     * never expire.
     *
     * @param DateInterval|int|null $value The number of seconds that table metadata can remain valid in cache.
     *
     * @see setEnabled()
     */
    public function setDuration(int|null|DateInterval $value): void
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
     * @see setEnabled()
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
            return (strpbrk($key, '{}()/\@:') !== false || $length < 1 || $length > 64) ? md5($key) : $key;
        }

        $key = json_encode($key);

        if ($key === false) {
            throw new PsrInvalidArgumentException('Invalid key. ' . json_last_error_msg());
        }

        return md5($key);
    }

    /**
     * Add key to tag. If tag is empty, do nothing.
     *
     * @throws InvalidArgumentException
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
