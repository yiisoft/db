<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Factory\Definitions\DefinitionInterface;
use Yiisoft\Factory\Definitions\Normalizer;
use Yiisoft\Factory\Factory;
use Yiisoft\Profiler\ProfilerInterface;

final class ProfilerFactory extends Factory
{
    private static ?ContainerInterface $container = null;
    private static ?DefinitionInterface $definition = null;
    private static Factory $profilerFactory;

    private function __construct(ContainerInterface $container = null)
    {
        self::$container = $container;
        self::$definition = Normalizer::normalize(ProfilerInterface::class);
    }

    public static function initialize(ContainerInterface $container = null): void
    {
        self::$profilerFactory = new self($container);
    }

    /**
     * Creates a `ProfilerInterface` instance.
     *
     * @throws RuntimeException If the created object is not an instance of the `ProfilerInterface`.
     *
     * @return ProfilerInterface The `ProfilerInterface` instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function run(): ProfilerInterface
    {
        $profiler = self::$definition->resolve(self::$container);

        if (!($profiler instanceof ProfilerInterface)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "Psr\Log\LoggerInterface".',
                (is_object($profiler) ? get_class($profiler) : gettype($profiler))
            ));
        }

        return $profiler;
    }
}
