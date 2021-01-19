<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Factory\Definitions\Normalizer;
use Yiisoft\Factory\Exceptions\InvalidConfigException;
use Yiisoft\Profiler\ProfilerInterface;

final class LazyConnectionDependencies
{
    private ContainerInterface $container;
    private ?LoggerInterface $logger = null;
    private ?ProfilerInterface $profiler = null;
    private ?QueryCache $queryCache = null;
    private ?SchemaCache $schemaCache = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get `LoggerInterface` instance.
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     *
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface
    {
        if ($this->logger !== null) {
            return $this->logger;
        }

        $this->logger = $this->create(LoggerInterface::class);

        return $this->logger;
    }

    /**
     * Get `ProfilerInterface` instance.
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     *
     * @return ProfilerInterface
     */
    public function profiler(): ProfilerInterface
    {
        if ($this->profiler !== null) {
            return $this->profiler;
        }

        $this->profiler = $this->create(ProfilerInterface::class);
        return $this->profiler;
    }

    /**
     * Get `QueryCache` instance.
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     *
     * @return QueryCache
     */
    public function queryCache(): QueryCache
    {
        if ($this->queryCache !== null) {
            return $this->queryCache;
        }

        $this->queryCache = $this->create(QueryCache::class);
        return $this->queryCache;
    }

    /**
     * Get `SchemaCache` instance.
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     *
     * @return SchemaCache
     */
    public function schemaCache(): SchemaCache
    {
        if ($this->schemaCache !== null) {
            return $this->schemaCache;
        }

        $this->schemaCache = $this->create(SchemaCache::class);
        return $this->schemaCache;
    }

    /**
     * Creates an instance of the specified class.
     *
     * @param string $class
     *
     * @throws InvalidConfigException
     * @throws RuntimeException If the created object is not an instance of the `LoggerInterface`.
     *
     * @return LoggerInterface|ProfilerInterface|QueryCache|SchemaCache The created instance.
     *
     * @psalm-suppress RedundantConditionGivenDocblockType
     * @psalm-suppress DocblockTypeContradiction
     */
    private function create(string $class): object
    {
        $definition = Normalizer::normalize($class);
        $instance = $definition->resolve($this->container);

        if (!($instance instanceof $class)) {
            throw new RuntimeException(sprintf(
                'The "%s" is not an instance of the "%s".',
                (is_object($instance) ? get_class($instance) : gettype($instance)),
                $class
            ));
        }

        return $instance;
    }
}
