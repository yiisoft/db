<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\CacheKeyNormalizer;

final class SchemaCache
{
    private CacheInterface $cache;
    private bool $enabled = true;
    private int $duration = 3600;
    private array $exclude = [];
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

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function isExclude(string $value): bool
    {
        return !in_array($value, $this->exclude, true);
    }

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
     * Number of seconds that table metadata can remain valid in cache. Use 0 to indicate that the cached data will
     * never expire.
     *
     * @param int $value
     *
     * {@see setEnable()}
     */
    public function setDuration(int $value): void
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
