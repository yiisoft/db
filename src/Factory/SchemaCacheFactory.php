<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Db\Cache\SchemaCache;

final class SchemaCacheFactory
{
    private static ?ContainerInterface $container = null;
    private static self $schemaCacheFactory;

    private function __construct(ContainerInterface $container = null)
    {
        self::$container = $container;
    }

    public static function initialize(ContainerInterface $container = null): void
    {
        self::$schemaCacheFactory = new self($container);
    }

    /**
     * Get `SchemaCache` instance.
     *
     * @throws RuntimeException If the get object is not an instance of the `SchemaCache`.
     *
     * @return SchemaCache The `SchemaCache` instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function get(): SchemaCache
    {
        $schemaCache = self::$container->get(SchemaCache::class);

        if (!($schemaCache instanceof SchemaCache)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "Psr\Log\LoggerInterface".',
                (is_object($schemaCache) ? get_class($schemaCache) : gettype($schemaCache))
            ));
        }

        return $schemaCache;
    }
}
