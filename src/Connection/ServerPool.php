<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Countable;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Factory\Exceptions\InvalidConfigException as FactoryInvalidConfigException;
use Yiisoft\Factory\FactoryInterface;

use function count;
use function md5;
use function shuffle;

class ServerPool implements Countable
{
    /**
     * @var int Number of failed attempts, after which the server will be temporarily disabled. Set to 0 if you do not need to disabled the servers.
     */
    public int $numberAttemptsBeforeSeverDisable = 3;
    /**
     * @var bool We are trying to connect to servers in a random order.
     */
    public bool $shuffle = true;
    /**
     * @var int Time in seconds for which the unavailable server will be disabled.
     */
    public int $timeoutBeforeRetryingConnect = 60;
    /**
     * @var bool If it was not possible to connect to any server, and this parameter is true, then we try to connect to the disabled servers.
     */
    public bool $tryDisabledServers = true;

    private ?CacheInterface $cache;
    private FactoryInterface $factory;
    /**
     * @var DSNInterface[]
     */
    private array $items;
    private LoggerInterface $logger;

    /**
     * ServerPool constructor.
     *
     * @param DSNInterface[]|array $items
     * @param FactoryInterface $factory
     * @param LoggerInterface $logger
     * @param CacheInterface|null $cache
     * @throws FactoryInvalidConfigException
     * @throws InvalidConfigException
     */
    public function __construct(array $items, FactoryInterface $factory, LoggerInterface $logger, CacheInterface $cache = null)
    {
        $this->addServers($items);
        $this->factory = $factory;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function addServer(DSNInterface $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @param $config
     * @throws FactoryInvalidConfigException
     * @throws InvalidConfigException
     * @see FactoryInterface::create
     */
    public function addServerByConfig($config): void
    {
        $dsnInst = $this->factory->create($config);
        if (!$dsnInst instanceof DSNInterface) {
            throw new InvalidConfigException('The factory returned a class that does not support the DSNInterface.');
        }
        $this->addServer($dsnInst);
    }

    /**
     * @param DSNInterface[]|array $items
     * @throws FactoryInvalidConfigException
     * @throws InvalidConfigException
     */
    public function addServers(array $items): void
    {
        foreach ($items as $item) {
            if ($item instanceof DSNInterface) {
                $this->addServer($item);
            } else {
                $this->addServerByConfig($item);
            }
        }
    }

    public function connect(bool $shuffle = null, bool $tryDisabledServers = null): ?ConnectionInterface
    {
        $items = $this->items;
        if ($shuffle ?? $this->shuffle) {
            shuffle($items);
        }

        $tryDisabledServers ??= $this->tryDisabledServers;
        $cache = $this->numberAttemptsBeforeSeverDisable > 0 && !($tryDisabledServers && count($items) <= 1) ? $this->cache : null;
        $disabledItems = [];

        foreach ($items as $item) {
            try {
                $dsn = (string)$item;
                $cacheKey = $cache ? md5(__CLASS__ . (string)$item) : null;

                $errorCount = $this->cacheGet($cache, $cacheKey);

                if ($errorCount && $errorCount >= $this->numberAttemptsBeforeSeverDisable) {
                    $disabledItems[] = [$cacheKey, $item, $dsn, $errorCount];
                    continue;
                }

                if ($conn = $this->tryingConnect(++$errorCount, $dsn, $item)) {
                    return $conn;
                }

                $this->cacheSet($cache, $cacheKey, $errorCount);
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [__METHOD__]);
            }
        }

        if (!$tryDisabledServers) {
            return null;
        }

        foreach ($disabledItems as $disabledItem) {
            try {
                [$cacheKey, $item, $dsn, $errorCount] = $disabledItem;

                if ($conn = $this->tryingConnect(++$errorCount, $dsn, $item)) {
                    return $conn;
                }

                $this->cacheSet($cache, $cacheKey, $errorCount);
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage(), [__METHOD__]);
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return DSNInterface[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    private function cacheGet(?CacheInterface $cache, ?string $cacheKey): int
    {
        if ($cache) {
            try {
                $errorCount = (int)($cache->get($cacheKey) ?: 0);
            } catch (Throwable|InvalidArgumentException $e) {
            }
        }
        return $errorCount ?? 0;
    }

    private function cacheSet(?CacheInterface $cache, ?string $cacheKey, int $errorCount): void
    {
        if ($cache) {
            $cache->set($cacheKey, $errorCount);
        }
    }

    private function tryingConnect(int $attemptNumber, string $dsn, DSNInterface $item): ?ConnectionInterface
    {
        if ($attemptNumber > 1) {
            $this->logger->info("Attempt #{$attemptNumber} to connect to the database with dsn: {$dsn}.");
        } else {
            $this->logger->debug("Trying to connect to the database with dsn: {$dsn}.");
        }

        try {
            $conn = $this->factory->create($item->getClass(), ['dsn' => $item]);

            if (!$conn instanceof ConnectionInterface) {
                throw new InvalidConfigException('The factory returned a class that does not support the ConnectionInterface.');
            }

            return $conn;
        } catch (InvalidConfigException|FactoryInvalidConfigException $e) {
            $this->logger->error($e->getMessage(), [__METHOD__]);
        } catch (Throwable $e) {
            $this->logger->alert($e->getMessage(), [__METHOD__]);
        }

        return null;
    }
}
