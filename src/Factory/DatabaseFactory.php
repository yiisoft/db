<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use Yiisoft\Factory\Factory;

final class DatabaseFactory
{
    private static ?Factory $factory = null;

    private function __construct()
    {
    }

    public static function initialize(ContainerInterface $container = null, array $definitions = []): void
    {
        self::$factory = new Factory($container, $definitions);
    }

    /**
     * Creates a Class defined by config passed.
     *
     * @param array $config parameters for creating a class.
     *
     * @throws \RuntimeException if factory was not initialized
     *
     * @return object
     */
    public static function createClass(array $config): object
    {
        if (self::$factory === null) {
            throw new \RuntimeException(
                'Database factory should be initialized with DatabaseFactory::initialize() call.'
            );
        }

        return self::$factory->create($config);
    }
}
