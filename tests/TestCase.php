<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use hiqdev\composer\config\Builder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Factory\DatabaseFactory;
use Yiisoft\Db\Exceptions\InvalidConfigException;
use Yiisoft\Di\Container;
use Yiisoft\Files\FileHelper;
use Yiisoft\Profiler\Profiler;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static array $params = [];
    protected Aliases $aliases;
    protected CacheInterface $cache;
    protected Connection $connection;
    protected ContainerInterface $container;
    protected ?string $driverName = null;
    protected LoggerInterface $logger;
    protected Profiler $profiler;
    protected array $dataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configContainer();
    }

    protected function configContainer(): void
    {
        $config = require Builder::path('tests');

        $this->container = new Container($config);

        $this->aliases = $this->container->get(Aliases::class);
        $this->cache = $this->container->get(CacheInterface::class);
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->profiler = $this->container->get(Profiler::class);

        DatabaseFactory::initialize($this->container, []);
    }

    /**
     * tearDown
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->aliases, $this->cache, $this->container, $this->logger, $this->profiler);

        parent::tearDown();
    }

    /**
     * Asserting two strings equality ignoring line endings.
     * @param string $expected
     * @param string $actual
     * @param string $message
     *
     * @return void
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that value is one of expected values.
     *
     * @param mixed $actual
     * @param array $expected
     * @param string $message
     */
    protected function assertIsOneOf($actual, array $expected, $message = ''): void
    {
        self::assertThat($actual, new IsOneOfAssert($expected), $message);
    }

    /**
     * Build the Data Source Name or DSN.
     *
     * @param array $config the DSN configurations
     *
     * @throws InvalidConfigException if 'driver' key was not defined
     *
     * @return string the formated DSN
     */
    protected function buildDSN(array $config): string
    {
        if (isset($config['driver'])) {
            $driver = $config['driver'];

            unset($config['driver']);

            $parts = [];

            foreach ($config as $key => $value) {
                $parts[] = "$key=$value";
            }

            return "$driver:" . implode(';', $parts);
        }

        throw new InvalidConfigException("Connection DSN 'driver' must be set.");
    }

    /**
     * Invokes a inaccessible method.
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution
     * @return mixed
     */
    protected function invokeMethod($object, $method, $args = [], $revoke = true)
    {
        $reflection = new \ReflectionObject($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        $result = $method->invokeArgs($object, $args);
        if ($revoke) {
            $method->setAccessible(false);
        }
        return $result;
    }

    protected function removeDirectory(string $basePath): void
    {
        $handle = opendir($basePath);

        if ($handle === false) {
            throw new \Exception("Unable to open directory: $basePath");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..' || $file === '.gitignore') {
                continue;
            }

            $path = $basePath . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                FileHelper::removeDirectory($path);
            } else {
                FileHelper::unlink($path);
            }
        }

        closedir($handle);
    }
}
