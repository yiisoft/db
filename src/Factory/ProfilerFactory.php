<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Profiler\ProfilerInterface;

final class ProfilerFactory
{
    private static ?ContainerInterface $container = null;
    private static self $profilerFactory;

    private function __construct(ContainerInterface $container = null)
    {
        self::$container = $container;
    }

    public static function initialize(ContainerInterface $container = null): void
    {
        self::$profilerFactory = new self($container);
    }

    /**
     * Get `ProfilerInterface` instance.
     *
     * @throws RuntimeException If the get object is not an instance of the `ProfilerInterface`.
     *
     * @return ProfilerInterface The `ProfilerInterface` instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function get(): ProfilerInterface
    {
        $profiler = self::$container->get(ProfilerInterface::class);

        if (!($profiler instanceof ProfilerInterface)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "Psr\Log\LoggerInterface".',
                (is_object($profiler) ? get_class($profiler) : gettype($profiler))
            ));
        }

        return $profiler;
    }
}
