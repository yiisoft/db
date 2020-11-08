<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;

class QueryCacheProxy
{
    private CacheInterface $cache;
    private ?int $duration;
    private ?Dependency $dependency;

    public function __construct(CacheInterface $cache, int $duration = null, Dependency $dependency = null)
    {
        $this->cache = $cache;
        $this->duration = $duration;
        $this->dependency = $dependency;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getDependency(): ?Dependency
    {
        return $this->dependency;
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $key, $default = null)
    {
        return $this->cache->get($key, $default);
    }

    /**
     * @param string $key
     * @param $value
     * @param int|null $duration
     * @param Dependency|null $dependency
     * @return bool
     */
    public function set(string $key, $value, int $duration = null, Dependency $dependency = null): bool
    {
        if (($duration ?? $this->duration) <= 0) {
            return false;
        }
        return $this->cache->set($key, $value, $duration ?? $this->duration, $dependency ?? $this->dependency);
    }
}
