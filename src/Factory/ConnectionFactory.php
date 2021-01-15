<?php

declare(strict_types=1);

namespace Yiisoft\Db\Factory;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Factory\Definitions\DefinitionInterface;
use Yiisoft\Factory\Definitions\Normalizer;
use Yiisoft\Factory\Exceptions\InvalidConfigException;

use function is_object;
use function get_class;
use function gettype;
use function sprintf;

/**
 * ConnectionFactory creates a database connection instance.
 */
final class ConnectionFactory
{
    /**
     * @var ContainerInterface Container for creating a database connection instance.
     */
    private ContainerInterface $container;

    /**
     * @var DefinitionInterface Definition for creating a database connection instance.
     */
    private DefinitionInterface $definition;

    /**
     * @param ContainerInterface $container Container for creating a database connection instance.
     * @param mixed $config The configuration for creating a database connection instance.
     * For more information, see {@see Normalizer::normalize()}.
     *
     * @throws InvalidConfigException If the configuration is invalid.
     */
    public function __construct(ContainerInterface $container, $config)
    {
        $this->container = $container;
        $this->definition = Normalizer::normalize($config);
    }

    /**
     * Creates a database connection instance.
     *
     * @throws RuntimeException If the created object is not an instance of the `ConnectionInterface`.
     *
     * @return ConnectionInterface The database connection instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    public function create(): ConnectionInterface
    {
        $сonnection = $this->definition->resolve($this->container);

        if (!($сonnection instanceof ConnectionInterface)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "Yiisoft\Db\Connection\ConnectionInterface".',
                (is_object($сonnection) ? get_class($сonnection) : gettype($сonnection))
            ));
        }

        return $сonnection;
    }
}
