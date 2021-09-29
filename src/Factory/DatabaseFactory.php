<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotFoundException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Factory\Factory;

final class DatabaseFactory
{
    private static ?Factory $factory = null;

    /**
     * @throws InvalidConfigException
     */
    public static function initialize(ContainerInterface $container = null, array $definitions = []): void
    {
        self::$factory = new Factory($container, $definitions);
    }

    /**
     * Get `SchemaCache` instance.
     *
     * @param array $config
     *
     * @throws CircularReferenceException|InvalidConfigException|NotFoundException|NotInstantiableException
     *
     * @return object
     */
    public static function connection(array $config): object
    {
        return self::createClass($config);
    }

    /**
     * Get `SchemaCache` instance.
     *
     * @throws CircularReferenceException|InvalidConfigException|NotFoundException|NotInstantiableException
     *
     * @return object
     */
    public static function schemaCache(): object
    {
        return self::createClass(SchemaCache::class);
    }

    /**
     * Creates a Class defined by config passed.
     *
     * @param array|string $config parameters for creating a class.
     *
     * @throws CircularReferenceException|InvalidConfigException|NotFoundException|NotInstantiableException
     */
    private static function createClass($config): object
    {
        if (self::$factory === null) {
            throw new RuntimeException(
                'Database factory should be initialized with DatabaseFactory::initialize() call.'
            );
        }

        return self::$factory->create($config);
    }
}
