<?php
declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use hiqdev\composer\config\Builder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection;
use Yiisoft\Di\Container;
use Yiisoft\Factory\Factory;

abstract class TestCase extends \PHPUnit\Framework\TestCase
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
     * @var Factory $factory
     */
    protected $factory;

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
     * Invokes a inaccessible method.
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution
     * @return mixed
     * @since 2.0.11
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
}
