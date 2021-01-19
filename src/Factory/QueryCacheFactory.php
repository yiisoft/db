<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Db\Cache\QueryCache;

final class QueryCacheFactory
{
    private static ?ContainerInterface $container = null;
    private static self $queryCacheFactory;

    private function __construct(ContainerInterface $container = null)
    {
        self::$container = $container;
    }

    public static function initialize(ContainerInterface $container = null): void
    {
        self::$queryCacheFactory = new self($container);
    }

    /**
     * Get `QueryCache` instance.
     *
     * @throws RuntimeException If the get object is not an instance of the `QueryCache`.
     *
     * @return QueryCache The `QueryCache` instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function get(): QueryCache
    {
        $queryCache = self::$container->get(QueryCache::class);

        if (!($queryCache instanceof QueryCache)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "Psr\Log\LoggerInterface".',
                (is_object($queryCache) ? get_class($queryCache) : gettype($queryCache))
            ));
        }

        return $queryCache;
    }
}
