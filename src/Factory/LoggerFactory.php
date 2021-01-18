<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Yiisoft\Factory\Definitions\DefinitionInterface;
use Yiisoft\Factory\Definitions\Normalizer;
use Yiisoft\Factory\Factory;

final class LoggerFactory extends Factory
{
    private static ?ContainerInterface $container = null;
    private static ?DefinitionInterface $definition = null;
    private static Factory $loggerFactory;

    private function __construct(ContainerInterface $container = null)
    {
        self::$container = $container;
        self::$definition = Normalizer::normalize(LoggerInterface::class);
    }

    public static function initialize(ContainerInterface $container = null): void
    {
        self::$loggerFactory = new self($container);
    }

    /**
     * Creates a `LoggerInterface` instance.
     *
     * @throws RuntimeException If the created object is not an instance of the `LoggerInterface`.
     *
     * @return LoggerInterface The `LoggerInterface` instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function run(): LoggerInterface
    {
        $logger = self::$definition->resolve(self::$container);

        if (!($logger instanceof LoggerInterface)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "Psr\Log\LoggerInterface".',
                (is_object($logger) ? get_class($logger) : gettype($logger))
            ));
        }

        return $logger;
    }
}
