<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class LoggerFactory
{
    private static ?ContainerInterface $container = null;
    private static self $loggerFactory;

    private function __construct(ContainerInterface $container = null)
    {
        self::$container = $container;
    }

    public static function initialize(ContainerInterface $container = null): void
    {
        self::$loggerFactory = new self($container);
    }

    /**
     * Get `LoggerInterface` instance.
     *
     * @throws RuntimeException If the get object is not an instance of the `LoggerInterface`.
     *
     * @return LoggerInterface The `LoggerInterface` instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function get(): LoggerInterface
    {
        $logger = self::$container->get(LoggerInterface::class);

        if (!($logger instanceof LoggerInterface)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "Psr\Log\LoggerInterface".',
                (is_object($logger) ? get_class($logger) : gettype($logger))
            ));
        }

        return $logger;
    }
}
