<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use Yiisoft\Factory\Factory;
use Yiisoft\Factory\FactoryInterface;

final class DatabaseFactory extends Factory
{
    private static ?FactoryInterface $factory = null;

    private function __construct(ContainerInterface $container = null, array $definitions = [])
    {
        parent::__construct($container, $definitions);
    }

    public static function initialize(ContainerInterface $container = null, array $definitions = []): void
    {
        self::$factory = new self($container, $definitions);
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
        if (static::$factory === null) {
            throw new \RuntimeException(
                'Database factory should be initialized with DatabaseFactory::initialize() call.'
            );
        }

        return static::$factory->create($config);
    }
}
