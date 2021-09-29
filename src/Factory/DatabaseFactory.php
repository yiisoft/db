<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotFoundException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Factory\Factory;

final class DatabaseFactory
{
    private static ?Factory $factory = null;

    private function __construct()
    {
    }

    /**
     * @throws InvalidConfigException
     */
    public static function initialize(ContainerInterface $container = null, array $definitions = []): void
    {
        self::$factory = new Factory($container, $definitions);
    }

    /**
     * Get `Connection` instance.
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
     * Creates a Class defined by config passed.
     *
     * @param array $config parameters for creating a class.
     *
     * @throws CircularReferenceException|InvalidConfigException|NotFoundException|NotInstantiableException
     */
    private static function createClass(array $config): object
    {
        if (self::$factory === null) {
            throw new RuntimeException(
                'Database factory should be initialized with DatabaseFactory::initialize() call.'
            );
        }

        $instance = self::$factory->create($config);

        if (!($instance instanceof $config['class'])) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "%s".',
                (is_object($instance) ? get_class($instance) : gettype($instance)),
                $config['class'],
            ));
        }

        return $instance;
    }
}
