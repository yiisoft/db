<?php
declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use hiqdev\composer\config\Builder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection;
use Yiisoft\Di\Container;
use Yiisoft\Factory\Factory;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var Aliases $aliases
     */
    protected $aliases;

    /**
     * @var ContainerInterface $container
     */
    protected $container;

    /**
     * @var array $params
     */
    protected static $params;

    /**
     * setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = require Builder::path('tests');

        $this->container = new Container($config);

        $this->aliases = $this->container->get(Aliases::class);
        $this->factory = $this->container->get(Factory::class);
    }

    /**
     * tearDown
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->container = null;
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
     * Returns a test configuration params from /config/params.php.
     *
     * @param string $name params name
     * @param mixed $default default value to use when param is not set.
     *
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require __DIR__ . '/data/config.php';
        }

        return isset(static::$params[$name]) ? static::$params[$name] : $default;
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
}
