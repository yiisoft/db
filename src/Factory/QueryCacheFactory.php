<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Factory\Definitions\DefinitionInterface;
use Yiisoft\Factory\Definitions\Normalizer;
use Yiisoft\Factory\Factory;

final class QueryCacheFactory extends Factory
{
    private static ?ContainerInterface $container = null;
    private static ?DefinitionInterface $definition = null;
    private static Factory $queryCacheFactory;

    private function __construct(ContainerInterface $container = null)
    {
        self::$container = $container;
        self::$definition = Normalizer::normalize(QueryCache::class);
    }

    public static function initialize(ContainerInterface $container = null): void
    {
        self::$queryCacheFactory = new self($container);
    }

    /**
     * Creates a `QueryCache` instance.
     *
     * @throws RuntimeException If the created object is not an instance of the `QueryCache`.
     *
     * @return QueryCache The `QueryCache` instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function run(): QueryCache
    {
        $queryCache = self::$definition->resolve(self::$container);

        if (!($queryCache instanceof QueryCache)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "Psr\Log\LoggerInterface".',
                (is_object($queryCache) ? get_class($queryCache) : gettype($queryCache))
            ));
        }

        return $queryCache;
    }
}
