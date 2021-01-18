<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Factory\Definitions\DefinitionInterface;
use Yiisoft\Factory\Definitions\Normalizer;
use Yiisoft\Factory\Factory;

final class SchemaCacheFactory extends Factory
{
    private static ?ContainerInterface $container = null;
    private static ?DefinitionInterface $definition = null;
    private static Factory $schemaCacheFactory;

    private function __construct(ContainerInterface $container = null)
    {
        self::$container = $container;
        self::$definition = Normalizer::normalize(SchemaCache::class);
    }

    public static function initialize(ContainerInterface $container = null): void
    {
        self::$schemaCacheFactory = new self($container);
    }

    /**
     * Creates a `SchemaCache` instance.
     *
     * @throws RuntimeException If the created object is not an instance of the `SchemaCache`.
     *
     * @return SchemaCache The `SchemaCache` instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function run(): SchemaCache
    {
        $schemaCache = self::$definition->resolve(self::$container);

        if (!($schemaCache instanceof SchemaCache)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "Psr\Log\LoggerInterface".',
                (is_object($schemaCache) ? get_class($schemaCache) : gettype($schemaCache))
            ));
        }

        return $schemaCache;
    }
}
