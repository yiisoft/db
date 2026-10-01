<?php

declare(strict_types=1);

namespace Yiisoft\Db\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * PSR-16 cache implementation that doesn't store anything.
 *
 * {@see SchemaCache} uses it when no cache implementation is provided.
 *
 * @internal
 */
final class NullCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        return true;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return array_fill_keys(
            $keys instanceof Traversable ? iterator_to_array($keys) : $keys,
            $default,
        );
    }

    public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
    {
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }
}
